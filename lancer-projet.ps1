# =============================================================================
#  Voyages Scolaires - Launcher tout-en-un
#  Menu : [1] Docker Compose (dev rapide)   [2] Kubernetes (k3d, demo soutenance)
# =============================================================================

# 'Continue' : les commandes natives (docker/kubectl/k3d) ecrivent sur stderr
# de facon normale -> ne pas transformer ca en erreur fatale.
$ErrorActionPreference = 'Continue'
Set-Location $PSScriptRoot

# --- Outils dans le PATH de cette session ---
$env:Path += ";C:\Program Files\Docker\Docker\resources\bin;$env:USERPROFILE\bin"

$K3D_VERSION = 'v5.9.0'
$CLUSTER     = 'tp-devops'
$IMAGE       = 'voyages:local'
$APIPORT     = '6445'

function Ensure-Docker {
    docker info *> $null
    if ($LASTEXITCODE -eq 0) { Write-Host "[OK] Docker pret." -ForegroundColor Green; return }
    Write-Host "Demarrage de Docker Desktop..." -ForegroundColor Yellow
    Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe" -ErrorAction SilentlyContinue
    for ($i = 0; $i -lt 40; $i++) {
        Start-Sleep 5
        docker info *> $null
        if ($LASTEXITCODE -eq 0) { Write-Host "[OK] Docker pret." -ForegroundColor Green; return }
    }
    throw "Docker n'a pas demarre. Ouvre Docker Desktop manuellement puis relance."
}

function Ensure-K3d {
    if (Get-Command k3d.exe -ErrorAction SilentlyContinue) { return }
    Write-Host "Installation de k3d ($K3D_VERSION)..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Force "$env:USERPROFILE\bin" | Out-Null
    Invoke-WebRequest -Uri "https://github.com/k3d-io/k3d/releases/download/$K3D_VERSION/k3d-windows-amd64.exe" `
        -OutFile "$env:USERPROFILE\bin\k3d.exe"
}

function Open-App { Start-Process "http://localhost:8080" }

# -----------------------------------------------------------------------------
#  MODE 1 : Docker Compose
# -----------------------------------------------------------------------------
function Start-Compose {
    Ensure-Docker

    # Liberer le port 8080 si un cluster k3d le detient
    if (Get-Command k3d.exe -ErrorAction SilentlyContinue) {
        if ((k3d cluster list 2>$null) -match $CLUSTER) {
            Write-Host "Suppression du cluster k3d pour liberer le port 8080..." -ForegroundColor Yellow
            k3d cluster delete $CLUSTER | Out-Null
        }
    }

    if (-not (Test-Path ".env")) {
        Write-Host "Creation du .env racine..."
        @"
DB_ROOT_PASSWORD=root_secret_password
DB_DATABASE=voyages_scolaires
DB_USERNAME=voyages_user
DB_PASSWORD=voyages_password
"@ | Set-Content -Encoding ascii ".env"
    }

    Write-Host "Demarrage de la base..."
    docker compose up -d db

    if (-not (Test-Path "www\vendor")) {
        Write-Host "Installation des dependances Composer (quelques minutes)..."
        docker compose run --rm app composer install --no-interaction
    }

    if (-not (Test-Path "www\.env")) {
        Write-Host "Creation du www\.env..."
        @"
APP_NAME="Voyages Scolaires"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=voyages_scolaires
DB_USERNAME=voyages_user
DB_PASSWORD=voyages_password
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
"@ | Set-Content -Encoding ascii "www\.env"
        docker compose run --rm app php artisan key:generate
    }

    Write-Host "Demarrage de la stack complete..."
    docker compose up -d
    Write-Host "Attente de la base (15s)..."
    Start-Sleep 15
    Write-Host "Migrations + donnees..."
    docker compose exec -T app php artisan migrate --seed --force
    docker compose exec -T app php artisan db:seed --class=DemoVoyagesSeeder --force

    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "  MODE DOCKER COMPOSE - PRET" -ForegroundColor Cyan
    Write-Host "  App     : http://localhost:8080"
    Write-Host "  Adminer : http://localhost:8081"
    Write-Host "  Comptes (mdp: password): enseignant@test.fr / admin@test.fr / parent@test.fr / eleve@test.fr"
    Write-Host "==================================================" -ForegroundColor Cyan
    Open-App
}

# -----------------------------------------------------------------------------
#  MODE 2 : Kubernetes (k3d)
# -----------------------------------------------------------------------------
function Start-K8s {
    Ensure-Docker
    Ensure-K3d

    Write-Host "Arret de la stack Compose (libere le port 8080)..."
    docker compose down 2>$null | Out-Null

    # Image de prod : on reconstruit systematiquement. Grace au cache Docker,
    # c'est quasi instantane si le code n'a pas change, et toujours a jour sinon.
    Write-Host "Construction de l'image de production (cache Docker si inchange)..." -ForegroundColor Yellow
    docker build -t $IMAGE -f docker/php/Dockerfile.prod .

    # Cluster propre (from scratch = critere Phase 4)
    if ((k3d cluster list 2>$null) -match $CLUSTER) {
        Write-Host "Recreation du cluster (etat propre)..." -ForegroundColor Yellow
        k3d cluster delete $CLUSTER | Out-Null
    }
    Write-Host "Creation du cluster k3d..."
    k3d cluster create $CLUSTER --servers 1 --agents 2 --port "8080:80@loadbalancer" --api-port "127.0.0.1:$APIPORT"

    Write-Host "Import de l'image dans le cluster..."
    k3d image import $IMAGE -c $CLUSTER

    # Fiabilise l'acces API (souci host.docker.internal sous Windows)
    kubectl config set-cluster "k3d-$CLUSTER" --server="https://127.0.0.1:$APIPORT" | Out-Null

    # Manifestes generes : image locale + secret reel (dossier gitignore)
    $run = ".k8s-run"
    if (Test-Path $run) { Remove-Item $run -Recurse -Force }
    New-Item -ItemType Directory -Force $run | Out-Null
    Copy-Item "k8s\*.yaml" $run
    Remove-Item "$run\secret.example.yaml" -ErrorAction SilentlyContinue

    foreach ($f in @("$run\deployment.yaml", "$run\migrate-job.yaml")) {
        (Get-Content $f) -replace 'image: ghcr.io/billalbzn/voyages-scolaires-bb_kf_nf:latest',
            "image: $IMAGE`n          imagePullPolicy: Never" | Set-Content $f
    }

    $appkey = "base64:" + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
    @"
apiVersion: v1
kind: Secret
metadata:
  name: laravel-secrets
type: Opaque
stringData:
  APP_KEY: "$appkey"
  DB_USERNAME: "voyages_user"
  DB_PASSWORD: "voyages_password"
  DB_ROOT_PASSWORD: "root_secret_password"
"@ | Set-Content -Encoding ascii "$run\secret.yaml"

    Write-Host "Deploiement (kubectl apply)..."
    kubectl apply -f (Resolve-Path $run).Path

    Write-Host "Attente des migrations (crash-and-retry normal au debut)..."
    for ($i = 0; $i -lt 36; $i++) {
        $s = kubectl get job laravel-migrate -o jsonpath='{.status.succeeded}' 2>$null
        if ($s -eq '1') { break }
        Start-Sleep 5
    }

    Write-Host "Donnees de demonstration..."
    $pod = kubectl get pod -l app=laravel -o jsonpath='{.items[0].metadata.name}'
    try { kubectl exec $pod -- php artisan db:seed --force 2>$null } catch {}
    try { kubectl exec $pod -- php artisan db:seed --class=DemoVoyagesSeeder --force 2>$null } catch {}

    Write-Host ""
    kubectl get pods
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "  MODE KUBERNETES (k3d) - PRET" -ForegroundColor Cyan
    Write-Host "  App servie par le cluster : http://localhost:8080"
    Write-Host "  Voir les pods   : kubectl get pods"
    Write-Host "  Supprimer       : k3d cluster delete $CLUSTER"
    Write-Host "==================================================" -ForegroundColor Cyan
    Open-App
}

# -----------------------------------------------------------------------------
#  MENU
# -----------------------------------------------------------------------------
Write-Host ""
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "     VOYAGES SCOLAIRES - LANCEMENT" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host "  [1] Docker Compose   (dev rapide, ~30s)"
Write-Host "  [2] Kubernetes k3d   (demo soutenance, ~5min)"
Write-Host "  [Q] Quitter"
Write-Host ""
$choix = Read-Host "Votre choix"

switch ($choix.Trim().ToUpper()) {
    '1' { Start-Compose }
    '2' { Start-K8s }
    'Q' { return }
    default { Write-Host "Choix invalide." -ForegroundColor Red }
}
