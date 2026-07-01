@echo off
setlocal
title Voyages Scolaires - Arret
cd /d "%~dp0"
set "PATH=%PATH%;C:\Program Files\Docker\Docker\resources\bin"

echo Arret des conteneurs du projet...
docker compose down
echo.
echo [OK] Conteneurs arretes. Les donnees de la base sont conservees ^(volume db_data^).
echo Relance avec start-projet.bat
pause
