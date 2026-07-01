# Trame de soutenance — Voyages Scolaires (DevOps & Laravel)

> **Format (trinôme)** : 25 min. Note **individuelle** : 2 notes /20 (une DevOps, une Laravel).
> Règle d'or : **chacun couvre les DEUX modules sur son périmètre**. Un module non défendu = note basse dessus.
> Support : diaporama + **démo en direct**. L'historique Git prouve la contribution.

## Fil conducteur (le sens de la trame)
> « Une même application, suivie du **besoin métier** jusqu'à son **exploitation supervisée**. »

Chacun possède une **tranche verticale** cohérente — du code Laravel à sa mise en production DevOps :

| Membre | Tranche = fonctionnalité Laravel + sa partie DevOps | Le « chapitre » qu'il raconte |
|---|---|---|
| **Billal** | Authentification & rôles **→** livraison de l'image (CI/CD) | « Qui a le droit, et comment le code devient une image livrée » |
| **Karim** | Voyages & formalités (CRUD) **→** exécution stateless sur le cluster | « Le cœur métier, et comment il tourne, répliqué, sans état » |
| **Nolan** | Participants, autorisation, API **→** persistance & sauvegarde | « Les interactions, et où vivent les données durables » |

Ordre de passage conseillé : **Billal → Karim → Nolan** (on suit à la fois la chaîne DevOps *build → deploy → data* et la logique Laravel *auth → CRUD → API*).

---

## Déroulé minuté (25 min)
| Phase | Durée | Qui |
|---|---|---|
| Contexte + architecture d'ensemble | 3 min | Groupe |
| Partie individuelle Billal (Laravel + DevOps) | 4 min | Billal |
| Partie individuelle Karim (Laravel + DevOps) | 4 min | Karim |
| Partie individuelle Nolan (Laravel + DevOps) | 4 min | Nolan |
| Questions individuelles du jury | 9 min | chacun |
| Bilan / questions ouvertes | 1 min | Groupe |

## Plan de diaporama (~13 slides)
1. **Titre** + noms + 1 ligne « qui fait quoi »
2. **Contexte & besoin** : plateforme de gestion de voyages scolaires (enseignants organisent, parents/élèves accèdent, rôles, RGPD)
3. **Architecture d'ensemble** (schéma) : navigateur → Ingress Traefik → 2 pods Laravel → MariaDB (StatefulSet) ; et la chaîne `git push → CI → image GHCR → cluster`
4–5. **Billal** : Auth/Rôles/Policies · Dockerfile.prod + CI/CD
6–7. **Karim** : Modèle + CRUD Voyages/Formalités · k8s stateless + sessions
8–9. **Nolan** : Participants/autorisation/API · k8s stateful + backup
10. **3 décisions d'architecture**
11. **Démo en direct** (scénario ci-dessous)
12. **Bilan** : acquis, limites connues, pistes
13. **Merci / questions**

---

## Intro commune (3 min) — à se répartir
- **Le besoin** (1 phrase) : centraliser l'organisation des voyages, l'inscription des élèves, l'autorisation parentale et les formalités, avec un accès par rôle.
- **La stack** : Laravel 13 + MariaDB, conteneurisé (Docker), livré par **CI/CD** (GitHub Actions → GHCR), déployé sur **Kubernetes (k3d)**.
- **Le schéma d'architecture** (slide 3) : montrer le flux requête (Ingress → pods → base) **et** le flux livraison (push → tests → image → cluster).
- Annoncer **qui présente quoi**.

---

## 🧑‍💻 BILLAL — « Qui a le droit, et comment le code est livré »

### Partie Laravel — Authentification & contrôle d'accès
- **Breeze (stack Blade)** : `register` / `login` / `logout` + gestion de profil.
- **Rôles** : migration `add_role_to_users_table` → colonne `enum('eleve','parent','enseignant','admin')` (défaut `eleve`, principe du moindre privilège) ; `role` ajouté au `$fillable` du modèle `User`.
- **VoyagePolicy** (règles centralisées) :
  - `create` : enseignant ou admin
  - `update` : admin **ou** propriétaire du voyage (`$user->id === $voyage->user_id`)
  - `delete` : admin uniquement
- Appliquée via **`Gate::authorize()`** dans les contrôleurs et **`@can`** dans les vues.
- **À montrer** : connecté en `eleve@test.fr` → pas de bouton « Nouveau voyage » ; en `enseignant@test.fr` → le bouton apparaît.

### Partie DevOps — De l'image à la livraison continue
- **`docker/php/Dockerfile.prod`** : base `php:8.3-apache`, `COPY www/ .` (le code entre dans l'image), `composer install --no-dev --optimize-autoloader`, DocumentRoot → `public/`, `chown` de `storage`. **`.dockerignore`** exclut `.env`/secrets → **aucun secret dans l'image**.
- **`.github/workflows/cicd.yaml`** — 2 jobs :
  1. `tests` : `php artisan test` (sur chaque push)
  2. `build-push` (`needs: tests`) : build **multi-arch** (amd64+arm64) → **GHCR**, tags **`:latest`** et **`:sha`**
- **À montrer** : onglet **Actions** vert, le **package GHCR**, expliquer que l'échec des tests bloque la publication.

### Questions probables (avec réponses)
- *« Pourquoi `Gate::authorize` et pas `$this->authorize` ? »* → la méthode du contrôleur de base a été retirée en Laravel 11 ; on passe par la **façade `Gate`**.
- *« Pourquoi une colonne enum plutôt qu'une table de rôles ? »* → 4 rôles fixes → enum simple et lisible ; une table `roles/permissions` (ex. package Spatie) serait justifiée si les rôles devenaient dynamiques.
- *« Pourquoi 2 jobs dans la CI ? »* → séparation **tests / livraison** : `needs: tests` garantit qu'**aucune image n'est publiée si un test échoue** → la prod est protégée.
- *« À quoi sert le tag `:sha` ? »* → **traçabilité** : chaque image est liée à un commit précis → rollback possible ; `:latest` sert au déploiement courant.
- *« Pourquoi `--no-dev` ? »* → pas d'outils de dev (Faker, Pest) dans l'image finale → plus **légère** et **sûre**.
- *« Y a-t-il un secret dans l'image ? »* → **non** : `.dockerignore` exclut `www/.env` ; les secrets viennent du **Secret Kubernetes** à l'exécution.

---

## 🧑‍💻 KARIM — « Le cœur métier, et comment il tourne, répliqué »

### Partie Laravel — Modèle de données, CRUD Voyages & Formalités
- **MVC** : `Route::resource('voyages', VoyageController::class)` → 7 routes ; contrôleur (index/create/store/show/edit/update/destroy) ; vues Blade.
- **Migrations & Eloquent** : table `voyages` (destination, date_depart, date_retour, places_max, `user_id` FK cascade). Relations : `Voyage hasMany participants`, `hasMany documents`, `belongsTo responsable (User)`.
- **Validation** : `destination` requis, `date_depart` **after:today**, `date_retour` **after:date_depart**, `places_max` entre 1 et 200.
- **Formalités (Documents)** : modèle `Document`, `DocumentController` (upload validé `mimes:pdf,jpg,png|max:5120`, `Storage`, `download`, `destroy`), relation `Voyage hasMany documents`.
- **À montrer** : créer un voyage (dates cohérentes), **ajouter une formalité** (upload), la **télécharger**.

### Partie DevOps — Déploiement stateless sur Kubernetes
- **`deployment.yaml`** : `replicas: 2`, image GHCR, **probes** `liveness`/`readiness` sur **`/up`** (route santé native Laravel).
- **`service.yaml`** (ClusterIP, lien par label) + **`ingress.yaml`** (Traefik, lien par nom).
- **`configmap.yaml`** (config non sensible) vs **`secret.yaml`** (APP_KEY, mots de passe, **hors git**) — règle : *« montrable en soutenance sans rougir ? »*.
- **La panne des sessions** (le moment fort) : avec 2 replicas et `SESSION_DRIVER=file`, on est déconnecté aléatoirement (chaque pod a son disque). **Solution : `SESSION_DRIVER=database`** → l'état est partagé. « Une app répliquée doit être **stateless**. »
- **À montrer** : `kubectl get pods` (2 replicas + mariadb + migrate `Completed`) ; `kubectl delete pod <laravel>` → **recréé automatiquement, sans coupure** ; login stable.

### Questions probables (avec réponses)
- *« Pourquoi `after:date_depart` ? »* → règle métier : le retour ne peut pas précéder le départ.
- *« `hasMany` vs `belongsTo` ? »* → un voyage a plusieurs participants (`hasMany`) ; un participant appartient à un voyage (`belongsTo`) ; la clé étrangère est côté `participants`.
- *« Pourquoi 2 replicas ? »* → **haute disponibilité** + prouver le fonctionnement **stateless**.
- *« Pourquoi les sessions en base ? »* → sinon la session vit sur le disque d'un pod ; le load-balancer envoie la requête suivante sur un autre pod → déconnexion. La base est un **état partagé** entre tous les pods.
- *« ConfigMap vs Secret ? »* → config **publique/versionnable** vs **données sensibles** (base64 + contrôle d'accès au cluster).
- *« Un pod meurt, que se passe-t-il ? »* → le **Deployment** maintient le nombre de replicas → recréation auto ; la `readinessProbe` retire un pod malade du load-balancing le temps qu'il guérisse.
- *« Et les fichiers uploadés en multi-replicas ? »* (piège) → un fichier sur le pod A n'est pas sur le pod B → il faudrait un **stockage objet partagé** (S3/MinIO). **Limite connue**, évolution Phase 4+.

---

## 🧑‍💻 NOLAN — « Les interactions, et où vivent les données durables »

### Partie Laravel — Participants, autorisation parentale & API REST
- **Participants** : `ParticipantController@store` (inscription, **anti-doublon**, **contrôle des places**), `autoriser()` (validation parentale), `destroy`. Route dédiée **`participants.autoriser`** (PATCH).
- **Autorisation** : `ParticipantPolicy::autoriser` réservée **parent/admin** (un élève ne s'auto-autorise pas → 403). Relations : `Participant belongsTo user & voyage`, `User hasMany participations`.
- **API REST** : `routes/api.php`, `VoyageApiController` (index `paginate` + `withCount`, show `load('participants.user')`, store/update/destroy avec `Gate`, endpoint **`/voyages/{voyage}/participants`**). Auth **Sanctum** (token `Bearer`). **Collection Bruno** dans `api-tests/`.
- **À montrer** : s'inscrire en élève, **valider en parent** ; puis **Bruno** → `/api/voyages` **401 sans token**, **200 avec token**, création **403** en élève.

### Partie DevOps — Base persistante & sauvegarde
- **`mariadb-statefulset.yaml`** : `StatefulSet` (identité stable `mariadb-0`) + **`volumeClaimTemplates`** (PVC `local-path`) = **stockage persistant**. Service **headless** `mariadb`.
- **`migrate-job.yaml`** : les migrations sont un **Job** (exécuté une fois, `--force`), pas au démarrage du pod. **Crash-and-retry** tant que la base n'est pas prête.
- **`backup-cronjob.yaml`** : `CronJob` (3h du matin) → `mariadb-dump` vers un **PVC** dédié. Test : `kubectl create job test-backup --from=cronjob/mariadb-backup`.
- **À montrer** : `kubectl get statefulset` ; déclencher le backup, **montrer le dump** `dump-AAAA-MM-JJ.sql` dans le PVC.

### Questions probables (avec réponses)
- *« Pourquoi Sanctum (token) et pas la session pour l'API ? »* → l'API est **stateless**, destinée à un front mobile/React → **token Bearer** dans l'en-tête, pas de cookie de session.
- *« Pourquoi un Job pour les migrations et pas au boot du pod ? »* → une migration doit s'exécuter **une seule fois**, de façon contrôlée ; sinon N replicas migreraient en parallèle. Le Job réussit (ou échoue proprement).
- *« StatefulSet vs Deployment ? »* → une base a besoin d'une **identité réseau et d'un stockage stables** (StatefulSet + PVC) ; un Deployment gère des pods **interchangeables et sans état**.
- *« Un backup dans le cluster, c'est un vrai backup ? »* → **non** (règle **3-2-1** : 3 copies, 2 supports, 1 **hors site**). Notre CronJob = la **1re copie automatisée** ; il faudrait l'exporter hors cluster (stockage objet, rétention).
- *« Comment garantir que l'autorisation parentale est sûre ? »* → la Policy `autoriser` n'autorise que parent/admin ; un élève reçoit **403** (prouvé par le test `ParticipantTest`).
- *« `ReadWriteOnce` sur le PVC ? »* → le volume est monté en écriture par **un seul nœud à la fois** → adapté à une base MariaDB à 1 replica.

---

## 🎬 Démo en direct (scénario — à répéter)
> **Pré-chargé avant de commencer** : cluster déjà lancé (`lancer-projet.bat` → option 2), onglets ouverts, terminaux prêts.

1. **App sur http://localhost:8080** → login `enseignant@test.fr` *(Karim)*
2. **Créer un voyage** + **ajouter une formalité** (upload + download) *(Karim)*
3. **S'inscrire en élève**, puis **valider l'autorisation en parent** *(Nolan)*
4. **Bruno** : `/api/voyages` sans token → 401, avec token → 200 *(Nolan)*
5. `kubectl get pods` (2 replicas + mariadb + migrate Completed) *(Karim)*
6. **`kubectl delete pod <laravel>`** → recréé, app toujours accessible (résilience) *(Karim)*
7. **Actions GitHub vert** + **package GHCR** ; expliquer `git push` → pipeline *(Billal)*
8. *(option)* déclencher le **backup CronJob** et montrer le dump *(Nolan)*

**Plan B (obligatoire)** : captures d'écran de chaque étape + image locale déjà importée (`k3d image import voyages:local` + `imagePullPolicy: Never`). *« Une démo qui plante sans plan B coûte plus cher que pas de démo. »*

---

## 🏛️ Les 3 décisions d'architecture (slide 10)
Détaillées dans `CONTRIBUTIONS.md`. Qui défend quoi :
1. **Image unique Apache** (vs pod nginx + php-fpm) → **Billal**
2. **Driver de sessions `database`** (vs `file`) → **Karim**
3. **Tags `:latest` + `:sha`** → **Billal**
- **Nolan** défend en plus le choix **StatefulSet + backup** et la **règle 3-2-1**.

---

## ✅ Checklist jour J
- [ ] Cluster déjà créé et image déjà publiée **avant** de commencer
- [ ] Onglets/terminaux ouverts et **testés**
- [ ] `CONTRIBUTIONS.md` **relu la veille** par chacun (c'est l'antisèche)
- [ ] Slide « qui a fait quoi » dès le départ
- [ ] Chacun sait expliquer **son code ligne à ligne** et couvre **Laravel ET DevOps**
- [ ] Plan B prêt (captures + image locale)
- [ ] Assumer un **« je ne sais pas »** plutôt que bluffer
- [ ] Gérer le temps (4 min chacun, ne pas déborder)
