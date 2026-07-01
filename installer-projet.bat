@echo off
setlocal enableextensions
title Voyages Scolaires - Installation (PC vierge)
cd /d "%~dp0"

set "REPO_URL=https://github.com/Billalbzn/voyages-scolaires-BB_KF_NF.git"
set "PROJECT_DIR=voyages-scolaires-BB_KF_NF"

echo ============================================================
echo    VOYAGES SCOLAIRES - Installation automatique
echo ============================================================
echo.

REM --- Deja dans le projet ? On saute clonage + prerequis ---
if exist "lancer-projet.bat" (
    echo [i] Projet deja present dans ce dossier.
    goto :launch_here
)

REM --- 1) Git installe ? ------------------------------------------------
where git >nul 2>&1
if errorlevel 1 (
    echo [X] Git n'est PAS installe.
    echo     Telecharge-le ici :  https://git-scm.com/download/win
    echo     Installe-le ^(Next/Next/Next^), puis relance ce fichier.
    goto :fail
)
echo [OK] Git detecte.

REM --- 2) Docker Desktop installe ? ------------------------------------
if not exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" (
    echo [X] Docker Desktop n'est PAS installe.
    echo     Telecharge-le ici :  https://www.docker.com/products/docker-desktop/
    echo     Installe-le, redemarre le PC si demande, puis relance ce fichier.
    goto :fail
)
echo [OK] Docker Desktop detecte.

REM --- 3) Cloner le depot ----------------------------------------------
if exist "%PROJECT_DIR%\lancer-projet.bat" (
    echo [i] Le dossier "%PROJECT_DIR%" existe deja - clonage saute.
) else (
    echo.
    echo [..] Clonage du depot GitHub...
    echo      ^(GitHub peut demander ton login / mot de passe si le repo est prive^)
    git clone "%REPO_URL%" "%PROJECT_DIR%"
    if errorlevel 1 (
        echo [X] Le clonage a echoue.
        echo     Verifie ta connexion Internet et que tu as bien acces au depot
        echo     ^(demande a Billal de t'ajouter comme collaborateur sur GitHub^).
        goto :fail
    )
)
cd /d "%PROJECT_DIR%"

:launch_here
echo.
echo [OK] Installation terminee. Lancement du projet...
echo     ^(Choisis [1] Docker Compose pour verifier vite que tout marche^)
echo.
call lancer-projet.bat
goto :done

:fail
echo.
echo Installation interrompue. Corrige le point ci-dessus puis relance.
echo.
pause

:done
endlocal
