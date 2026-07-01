@echo off
setlocal
title Voyages Scolaires - Lancement
cd /d "%~dp0"

REM --- Ajoute Docker au PATH de cette session ---
set "PATH=%PATH%;C:\Program Files\Docker\Docker\resources\bin"

echo ==================================================
echo   Voyages Scolaires - lancement Docker
echo ==================================================
echo.

REM --- 1) Docker demarre-t-il ? sinon on lance Docker Desktop et on attend ---
docker info >nul 2>&1
if not errorlevel 1 goto dockerok
echo Docker ne repond pas. Demarrage de Docker Desktop...
start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
set /a tries=0
:waitdocker
timeout /t 5 /nobreak >nul
docker info >nul 2>&1
if not errorlevel 1 goto dockerok
set /a tries+=1
if %tries% lss 30 goto waitdocker
echo ERREUR : Docker n'a pas demarre. Ouvre Docker Desktop manuellement puis relance ce script.
pause
exit /b 1
:dockerok
echo [OK] Moteur Docker pret.
echo.

REM --- 2) .env racine (config Docker) ---
if not exist ".env" (
  echo Creation du .env racine...
  (
    echo DB_ROOT_PASSWORD=root_secret_password
    echo DB_DATABASE=voyages_scolaires
    echo DB_USERNAME=voyages_user
    echo DB_PASSWORD=voyages_password
  ) > ".env"
)

REM --- 3) Demarrage de la base de donnees ---
echo Demarrage de la base MariaDB...
docker compose up -d db

REM --- 4) Dependances Composer (uniquement si absentes) ---
if not exist "www\vendor" (
  echo Installation des dependances Composer ^(quelques minutes au premier lancement^)...
  docker compose run --rm app composer install --no-interaction
)

REM --- 5) www\.env (config Laravel) ---
set "FIRSTRUN="
if not exist "www\.env" (
  set "FIRSTRUN=1"
  echo Creation du www\.env ...
  (
    echo APP_NAME="Voyages Scolaires"
    echo APP_ENV=local
    echo APP_KEY=
    echo APP_DEBUG=true
    echo APP_URL=http://localhost:8080
    echo.
    echo LOG_CHANNEL=stack
    echo.
    echo DB_CONNECTION=mysql
    echo DB_HOST=db
    echo DB_PORT=3306
    echo DB_DATABASE=voyages_scolaires
    echo DB_USERNAME=voyages_user
    echo DB_PASSWORD=voyages_password
    echo.
    echo SESSION_DRIVER=database
    echo SESSION_LIFETIME=120
    echo CACHE_STORE=database
    echo QUEUE_CONNECTION=database
    echo MAIL_MAILER=log
  ) > "www\.env"
)

REM --- 6) Cle d'application (uniquement au premier lancement) ---
if defined FIRSTRUN (
  echo Generation de la cle d'application...
  docker compose run --rm app php artisan key:generate
)

REM --- 7) Demarrage de toute la stack ---
echo Demarrage de la stack complete...
docker compose up -d

REM --- 8) Attente de la base puis migrations + donnees de test ---
echo Attente du demarrage de la base ^(15s^)...
powershell -NoProfile -Command "Start-Sleep -Seconds 15" >nul 2>&1
echo Migrations + jeu de donnees de test...
docker compose exec -T app php artisan migrate --seed --force

echo.
echo ==================================================
echo   PROJET LANCE
echo   Application : http://localhost:8080
echo   Adminer     : http://localhost:8081
echo.
echo   Comptes de test ^(mot de passe : password^) :
echo     enseignant@test.fr  ^(cree des voyages^)
echo     admin@test.fr       ^(tout^)
echo     parent@test.fr      ^(valide l'autorisation^)
echo     eleve@test.fr       ^(s'inscrit^)
echo ==================================================
start "" http://localhost:8080
echo.
echo Pour tout arreter : ferme cette fenetre puis lance stop-projet.bat
echo.
pause
