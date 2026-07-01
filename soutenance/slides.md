---
marp: true
theme: gaia
paginate: true
header: 'Voyages Scolaires — Soutenance DevOps & Laravel'
footer: 'Billal · Karim · Nolan — Mastère ESI'
---

<!-- _class: lead -->
<!-- _paginate: false -->
<!-- _header: '' -->
<!-- _footer: '' -->

# Voyages Scolaires
### Plateforme de gestion de voyages scolaires

**Laravel 13 · Docker · CI/CD · Kubernetes**

Billal Bouziane · Karim Fadli · Nolan Felmit
Mastère ESI

---

# Qui présente quoi

| Membre | Laravel | DevOps |
|---|---|---|
| **Billal** | Auth · Rôles · Policies | Dockerfile.prod · CI/CD → GHCR |
| **Karim** | CRUD Voyages · Formalités | Kubernetes *stateless* · sessions |
| **Nolan** | Participants · API REST | Kubernetes *stateful* · backup |

> Chacun défend une **tranche verticale** : du code métier à son exploitation.

---

# Contexte & besoin

- Un établissement veut **gérer ses voyages scolaires**
- **Enseignants** : organiser les séjours (CRUD)
- **Parents / élèves** : inscription, **autorisation parentale**, formalités
- **Sécurité & accès par rôle** (élève, parent, enseignant, admin)

> Objectif : une application **de A à Z**, du code au **déploiement supervisé**.

---

# Architecture d'ensemble

```
  Navigateur :8080
        │
        ▼
   Ingress (Traefik)
        │
        ▼
  ┌───────────────┐      ┌──────────────┐
  │ 2× pod Laravel│─────▶│  MariaDB     │
  │  (stateless)  │      │ (StatefulSet)│
  └───────────────┘      └──────────────┘

  git push ─▶ GitHub Actions ─▶ image GHCR ─▶ cluster
              (tests)           (:latest/:sha)
```

---

<!-- _class: lead -->
# Billal
## Authentification & livraison de l'image

---

# Billal — Laravel : Auth & rôles

- **Laravel Breeze** (Blade) : register / login / logout / profil
- Colonne **`role` (enum)** : `eleve · parent · enseignant · admin`
- **VoyagePolicy** — règles centralisées :
  - `create` → enseignant / admin
  - `update` → admin **ou** propriétaire
  - `delete` → admin
- Appliquée via `Gate::authorize()` + `@can` dans les vues

> **Démo** : un élève ne voit pas « Nouveau voyage », un enseignant oui.

---

# Billal — DevOps : Dockerfile.prod + CI/CD

- **`Dockerfile.prod`** : `php:8.3-apache`, code **copié** dans l'image, `composer install --no-dev`, `.dockerignore` → **aucun secret embarqué**
- **Pipeline `.github/workflows/cicd.yaml`** :
  1. `tests` → `php artisan test`
  2. `build-push` (`needs: tests`) → image **multi-arch** → **GHCR**
- Tags **`:latest`** (déploiement) + **`:sha`** (traçabilité)

> Un test qui échoue **bloque** la publication de l'image.

---

<!-- _class: lead -->
# Karim
## Le cœur métier, exécuté répliqué

---

# Karim — Laravel : Voyages & Formalités

- **MVC** : `Route::resource('voyages')` → contrôleur + vues Blade
- **Eloquent** : `Voyage hasMany participants / documents`, `belongsTo responsable`
- **Validation** : `date_retour` **after** `date_depart`, places 1–200
- **Formalités** : upload de documents (passeport, assurance) validé + téléchargement

> **Démo** : créer un voyage, ajouter et télécharger une formalité.

---

# Karim — DevOps : Kubernetes *stateless*

- **Deployment** `replicas: 2` + **probes** sur `/up`
- **Service** (par label) + **Ingress** Traefik (par nom)
- **ConfigMap** (public) vs **Secret** (APP_KEY, mots de passe — hors git)
- **La panne** : `SESSION_DRIVER=file` → déconnexions en multi-replicas
  → **résolu** avec `SESSION_DRIVER=database`

> **Démo** : `kubectl delete pod` → recréé **sans coupure**.

---

<!-- _class: lead -->
# Nolan
## Interactions & données durables

---

# Nolan — Laravel : Participants & API

- **Inscription** : anti-doublon + contrôle des places
- **Autorisation parentale** : `ParticipantPolicy::autoriser` (parent/admin) — un élève → **403**
- **API REST** (`VoyageApiController`) : index paginé, endpoint `/voyages/{id}/participants`
- **Sanctum** : token `Bearer` · collection **Bruno** versionnée

> **Démo** : inscription → validation parent ; API 401 sans token, 200 avec.

---

# Nolan — DevOps : Kubernetes *stateful*

- **StatefulSet MariaDB** + **PVC** → stockage persistant, identité stable
- **Migrations = Job** (`--force`, une fois) → *crash-and-retry*
- **CronJob backup** (3h) → `mariadb-dump` vers un PVC dédié

> **Démo** : déclencher le backup, montrer le dump `.sql`.
> Question ouverte : *un backup dans le cluster est-il un vrai backup ?* (règle 3-2-1)

---

# 3 décisions d'architecture

1. **Image unique Apache** (vs nginx + php-fpm)
   → simplicité de déploiement *(Billal)*
2. **Sessions en `database`** (vs `file`)
   → app répliquée = **stateless** *(Karim)*
3. **Tags `:latest` + `:sha`**
   → déploiement **et** traçabilité/rollback *(Billal)*

> Détaillées dans `CONTRIBUTIONS.md`.

---

# Démonstration en direct

1. Login enseignant → **créer un voyage** + formalité
2. Élève **s'inscrit** → parent **valide l'autorisation**
3. **API** via Bruno (401 → 200 avec token)
4. `kubectl get pods` → 2 replicas + MariaDB + migrate `Completed`
5. `kubectl delete pod` → **résilience sans coupure**
6. **CI verte** + image sur **GHCR**

> **Plan B** prêt : captures + image locale importée.

---

# Bilan

- **Fait** : app Laravel complète, testée (38 tests), conteneurisée, livrée par CI/CD, déployée sur Kubernetes
- **Compris** : image = artefact, *stateless* obligatoire, config/secrets séparés
- **Limites connues** : uploads non partagés entre pods (→ stockage objet), backup à externaliser (3-2-1)

---

<!-- _class: lead -->
# Merci
### Questions ?

Billal · Karim · Nolan — Mastère ESI
