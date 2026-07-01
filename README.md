# Voyages scolaires — Projet fil rouge

Plateforme de gestion de voyages scolaires — Mastère ESI.
Application Laravel 13 déployable de A à Z : dev local en Docker Compose,
production sur cluster Kubernetes (k3d) via une chaîne CI/CD.

## Stack

Laravel 13 · MariaDB · Docker · GitHub Actions (CI/CD) · Kubernetes (k3d) · Sanctum (API)

## Fonctionnalités

- Authentification + 4 rôles (élève, parent, enseignant, admin) via Laravel Breeze + Policies
- CRUD Voyages (destination, dates, places, encadrant)
- Inscription des participants + validation de l'autorisation parentale
- Formalités : documents administratifs (passeport, assurance) par voyage
- API REST `/api/voyages` sécurisée par token Sanctum

---

## 1. Lancer en dev local (Docker Compose)

Prérequis : Docker Desktop.

```bash
git clone https://github.com/Billalbzn/voyages-scolaires-BB_KF_NF.git
cd voyages-scolaires-BB_KF_NF

cp .env.example .env          # renseigner DB_ROOT_PASSWORD, DB_DATABASE, DB_USERNAME, DB_PASSWORD

docker compose up -d db       # démarrer la base
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d          # toute la stack
docker compose exec app php artisan migrate --seed
```

Application : http://localhost:8080 — Adminer (base) : http://localhost:8081

Utilisateurs de test (mot de passe `password`) :
`eleve@test.fr`, `parent@test.fr`, `enseignant@test.fr`, `admin@test.fr`

## 2. Tester l'API (Bruno / Postman)

Collection dans `api-tests/collection.json`. Récupérer un token Sanctum puis
appeler `/api/voyages` avec l'en-tête `Authorization: Bearer <token>`.

## 3. Déployer sur le cluster (Kubernetes / k3d)

```bash
k3d cluster create tp-devops --servers 1 --agents 2 --port "8080:80@loadbalancer"

cp k8s/secret.example.yaml k8s/secret.yaml   # puis renseigner APP_KEY et les mots de passe
kubectl apply -f k8s/                         # déploie toute la stack

kubectl get pods -w                           # attendre Running / Completed
```

Application servie par le cluster : http://localhost:8080

- L'image est publiée automatiquement sur GHCR par le pipeline (`.github/workflows/cicd.yaml`)
  à chaque `git push` sur `main` (si les tests passent).
- Le `k8s/migrate-job.yaml` exécute les migrations ; le `backup-cronjob.yaml` sauvegarde la base.

## 4. Tests

```bash
docker compose exec app php artisan test
```

## Architecture & décisions

Voir `CONTRIBUTIONS.md` (répartition par membre + 3 décisions d'architecture).

## Équipe & rôles

- **Billal Bouziane** — Auth/Rôles/Policies · DevOps : Dockerfile.prod + CI/CD
- **Karim Fadli** — CRUD Voyages + Formalités · DevOps : k8s stateless (Deployment/Service/Ingress/Config)
- **Nolan Felmit** — Participants + API REST · DevOps : k8s stateful (MariaDB, migrations, backup)
