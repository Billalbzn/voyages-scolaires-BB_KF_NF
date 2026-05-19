# Voyages Scolaires

Plateforme de gestion de voyages scolaires — Projet fil rouge Mastère ESI.

Stack : Laravel 13 + Docker Compose + MariaDB.

## Prérequis

- Docker Desktop (Docker Engine 24+, Docker Compose v2+)

## Installation

1. Cloner le dépôt :

   git clone https://github.com/Billalbzn/voyages-scolaires-BB_KF_NF.git
   cd voyages-scolaires-BB_KF_NF

2. Créer le fichier .env à la racine à partir de l'exemple :

   cp .env.example .env

   Puis renseigner les valeurs (mots de passe de la base).

3. Démarrer la base de données :

   docker compose up -d db

4. Installer les dépendances Laravel :

   docker compose run --rm app composer install

5. Créer le fichier www/.env (config Laravel) et générer la clé :

   docker compose run --rm app php artisan key:generate

6. Démarrer la stack complète :

   docker compose up -d

7. Exécuter les migrations :

   docker compose exec app php artisan migrate

L'application est accessible sur http://localhost:8080

## Services

- web : Nginx (port 8080)
- app : PHP-FPM + Laravel 13
- db  : MariaDB 10.11
