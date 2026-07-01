@echo off
title Voyages Scolaires - Lancement
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0lancer-projet.ps1"
echo.
pause
