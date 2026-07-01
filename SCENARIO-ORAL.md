# Scénario de la soutenance — run-of-show

> Ton **factuel** (phrases déclaratives, pas de formules d'accroche). Chacun couvre Laravel **et** DevOps.
> Cluster déjà lancé (`lancer-projet.bat` → option 2) **avant** de commencer.

## Check-list d'avant-passage (10 min avant)
1. **Lancer le cluster** : `lancer-projet.bat` → **2** (Kubernetes). Attendre que `kubectl get pods` montre 2 pods `laravel` Running + `mariadb-0` Running + `laravel-migrate` Completed.
2. Vérifier **http://localhost:8080** (page d'accueil s'affiche).
3. **Générer un token API pour Bruno** (à coller dans la variable `token`) :
   ```
   $env:Path += ";C:\Program Files\Docker\Docker\resources\bin;$env:USERPROFILE\bin"
   $pod = kubectl get pod -l app=laravel -o jsonpath='{.items[0].metadata.name}'
   kubectl exec $pod -- php artisan tinker --execute="echo App\Models\User::where('email','enseignant@test.fr')->first()->createToken('demo')->plainTextToken;"
   ```
4. **Onglets ouverts et testés** :
   - Diaporama (prêt en plein écran)
   - Navigateur onglet A : `http://localhost:8080` (déconnecté)
   - Navigateur onglet B : fenêtre **privée** (pour comparer 2 rôles)
   - **Bruno** ouvert, requête `GET /api/voyages`, token collé
   - **GitHub → Actions** (dernier run vert)
   - **GitHub → Packages** (image GHCR)
   - Terminal 1 : `kubectl get pods` prêt
   - Terminal 2 : prêt pour `kubectl delete pod`
5. **Plan B** : dossier de **captures d'écran** ouvert (au cas où réseau/cluster KO).
6. (option) Pré-produire un dump backup pour le montrer vite :
   `kubectl create job test-backup --from=cronjob/mariadb-backup`

---

## PARTIE COMMUNE — Contexte & architecture (≈ 3 min)

### Slide 1 — Titre · *Karim* (~20 s)
- **À l'écran** : titre, stack, 3 noms.
- **Dit** : « Notre projet est une plateforme de gestion de voyages scolaires, développée en Laravel 13 et déployée avec Docker, une chaîne CI/CD et Kubernetes. Nous sommes trois : Billal, Karim, Nolan. »

### Slide 2 — Qui présente quoi · *Karim* (~30 s)
- **À l'écran** : tableau des périmètres.
- **Dit** : « Chacun présente une tranche complète du projet, côté Laravel et côté DevOps. Billal : authentification et chaîne d'intégration. Moi, Karim : les voyages et le déploiement stateless. Nolan : les participants, l'API, et la base de données dans le cluster. »

### Slide 3 — Contexte & besoin · *Billal* (~45 s)
- **À l'écran** : besoin métier, 4 rôles, RGPD. *(capture liste des voyages)*
- **Dit** : « Un établissement organise des séjours. Il y a quatre rôles : élève, parent, enseignant, admin. Les enseignants créent les voyages, les élèves s'inscrivent, les parents valident l'autorisation de sortie, et chaque voyage a des formalités comme le passeport ou l'assurance. Les données concernent des mineurs, d'où un contrôle d'accès par rôle. »

### Slide 4 — Architecture · *Nolan* (~1 min 15)
- **À l'écran** : schéma navigateur → Ingress → pods → MariaDB, et la chaîne CI/CD.
- **Dit** : « En haut, le flux d'exécution : le navigateur arrive sur l'Ingress Traefik, qui répartit vers deux pods Laravel, connectés à une base MariaDB en StatefulSet. En bas, la chaîne de livraison : un push sur main déclenche GitHub Actions, qui lance les 38 tests, construit une image publiée sur GHCR, puis le cluster déploie cette image. Chacun de nous détaille maintenant sa partie. »

---

## PARTIE 1 — BILLAL (≈ 4 min)

### Slide 5 — Séparateur Billal (~5 s)
- **Dit** : « Je présente l'authentification, les rôles, et la chaîne CI/CD. »

### Slide 6 — Laravel : Auth, rôles & policies (~1 min 30)
- **À l'écran** : Breeze, enum, VoyagePolicy, Gate/@can. *(capture élève vs enseignant)*
- **Dit** : « L'authentification utilise Laravel Breeze en Blade : inscription, connexion, déconnexion. Le rôle est une colonne enum à quatre valeurs. Les règles d'accès sont centralisées dans VoyagePolicy : la création est réservée aux enseignants et admins ; la modification à l'admin ou au propriétaire du voyage ; la suppression à l'admin uniquement. La policy est appliquée avec Gate::authorize dans les contrôleurs et la directive @can dans les vues. »
- **Démo (navigateur)** :
  1. Onglet A : se connecter en **enseignant@test.fr** → montrer le bouton **« Nouveau voyage »**.
  2. Onglet B (privé) : se connecter en **eleve@test.fr** → **le bouton n'apparaît pas**.
  - **Dit** : « Le même écran, deux rôles : l'élève n'a pas l'action de création. Une tentative directe renvoie une erreur 403. »

### Slide 7 — DevOps : image de prod & CI/CD (~1 min 30)
- **À l'écran** : Dockerfile.prod, --no-dev, .dockerignore, pipeline 2 jobs, tags. *(capture Actions + GHCR)*
- **Dit** : « L'image de production part de php:8.3-apache. Le code est copié dans l'image, les dépendances installées en --no-dev, et un .dockerignore garantit qu'aucun secret n'est embarqué. Le pipeline a deux jobs : d'abord les tests, ensuite la construction et la publication de l'image, qui ne s'exécute que si les tests passent. L'image est publiée sur GHCR avec deux tags : latest pour le déploiement, et le sha du commit pour la traçabilité. »
- **Démo (GitHub)** :
  1. Onglet **Actions** : montrer le dernier run **vert** (jobs `tests` puis `build-push`).
  2. Onglet **Packages** : montrer l'**image** avec les tags.
  - **Dit** : « Chaque push sur main rejoue cette chaîne automatiquement. »

---

## PARTIE 2 — KARIM (≈ 4 min)

### Slide 8 — Séparateur Karim (~5 s)
- **Dit** : « Je présente le CRUD des voyages et des formalités, puis le déploiement stateless sur Kubernetes. »

### Slide 9 — Laravel : CRUD Voyages & formalités (~1 min 30)
- **À l'écran** : MVC, CRUD, validation, relations, formalités. *(capture fiche voyage)*
- **Dit** : « L'organisation suit le modèle MVC : une route pointe vers un contrôleur, qui rend une vue Blade. Le CRUD des voyages gère la destination, les dates, le nombre de places et le responsable. La validation est côté serveur : la date de retour doit être après la date de départ, et le nombre de places entre 1 et 200. Les relations Eloquent lient un voyage à ses participants et à ses documents. Les formalités permettent d'attacher des documents à un voyage, avec upload et téléchargement. »
- **Démo (navigateur, connecté enseignant)** :
  1. **Créer un voyage** : remplir le formulaire (ex. Madrid, dates cohérentes, 30 places) → valider → il apparaît dans la liste.
  2. Ouvrir la **fiche du voyage** → section formalités → **ajouter un document** (titre + fichier) → **le télécharger**.
  - **Dit** : « Le voyage est créé, la formalité est ajoutée et récupérable. »

### Slide 10 — DevOps : Kubernetes stateless (~1 min 30)
- **À l'écran** : Deployment 2 replicas + probes, Service/Ingress, ConfigMap/Secret, sessions. *(capture kubectl get pods)*
- **Dit** : « L'application tourne en deux replicas, avec des probes liveness et readiness sur la route /up. Le Service et l'Ingress gèrent le routage. La configuration non sensible est dans un ConfigMap, les secrets comme l'APP_KEY et les mots de passe dans un Secret, hors du dépôt. Point important : avec deux replicas et des sessions stockées en fichier, on est déconnecté aléatoirement, car chaque pod a son propre disque. On a résolu ça en passant les sessions en base de données : l'état est partagé, l'application devient stateless. »
- **Démo (terminal + navigateur)** :
  1. Terminal 1 : `kubectl get pods` → montrer **2 pods laravel** + mariadb + migrate Completed.
  2. Terminal 2 : `kubectl delete pod <un-pod-laravel>` puis `kubectl get pods` → **un nouveau pod est recréé**.
  3. Navigateur : **rafraîchir** la page → **toujours connecté, aucune coupure**.
  - **Dit** : « Kubernetes recrée le pod automatiquement, et comme la session est en base, l'utilisateur reste connecté. »

---

## PARTIE 3 — NOLAN (≈ 4 min)

### Slide 11 — Séparateur Nolan (~5 s)
- **Dit** : « Je présente les participants, l'autorisation parentale et l'API, puis la base de données et les sauvegardes dans le cluster. »

### Slide 12 — Laravel : Participants, autorisation & API (~1 min 30)
- **À l'écran** : inscription, ParticipantPolicy, API, Sanctum, Bruno. *(capture Bruno)*
- **Dit** : « L'inscription d'un élève vérifie qu'il n'est pas déjà inscrit et que le voyage n'est pas complet. La validation de l'autorisation parentale est réservée au parent ou à l'admin par la ParticipantPolicy : un élève reçoit une erreur 403. L'application expose aussi une API REST sur /api/voyages : liste paginée, détail, et opérations selon le rôle. L'API est protégée par un token Sanctum en en-tête Authorization ; sans token, la réponse est 401. La collection Bruno versionnée permet de rejouer ces tests. »
- **Démo (navigateur + Bruno)** :
  1. Navigateur : en **élève**, **s'inscrire** à un voyage → statut « en attente d'autorisation ».
  2. En **parent**, ouvrir le voyage → **valider l'autorisation** → statut « autorisé ».
  3. Bruno : `GET /api/voyages` **sans token** → **401** ; ajouter le **token** → **200** avec le JSON.
  - **Dit** : « L'inscription est en attente, le parent la valide, et l'API répond en JSON une fois authentifiée. »

### Slide 13 — DevOps : Kubernetes stateful & sauvegardes (~1 min 30)
- **À l'écran** : StatefulSet, PVC, Job migrations, CronJob backup. *(capture dump)*
- **Dit** : « La base est un StatefulSet MariaDB, avec un Service headless pour une identité réseau stable et un PVC pour un stockage persistant : les données survivent au redémarrage du pod. Les migrations ne s'exécutent pas au démarrage de l'application, mais dans un Job dédié lancé une fois avec --force ; il est rejoué jusqu'au succès tant que la base n'est pas prête. Enfin, un CronJob réalise une sauvegarde régulière de la base sous forme de dump SQL. »
- **Démo (terminal)** :
  1. `kubectl get statefulset` → montrer `mariadb`.
  2. `kubectl create job test-backup --from=cronjob/mariadb-backup` puis `kubectl get jobs` → **Complete**.
  3. Montrer le dump : `kubectl exec <pod-verify> -- ls /backup` → `dump-AAAA-MM-JJ.sql` *(ou capture préparée)*.
  - **Dit** : « La sauvegarde produit un dump daté dans son volume. »

---

## CLÔTURE (groupe)

### Slide 14 — 3 décisions d'architecture (~1 min)
- Chacun énonce **sa** décision, en une phrase :
  - **Billal** : « Nous avons choisi une image unique Apache plutôt qu'un pod nginx + php-fpm, pour un déploiement plus simple à opérer. »
  - **Karim** : « Les sessions sont en base et non en fichier, parce qu'une application répliquée doit être stateless. »
  - **Nolan** : « Nous poussons deux tags, latest et sha : latest pour le déploiement courant, sha pour la traçabilité et le rollback par commit. »

### Slide 15 — Démonstration (rappel / secours)
- Si une démo a été coupée par manque de temps, la faire ici (priorité : **résilience** `kubectl delete pod`).
- Sinon : « Ces étapes viennent d'être montrées en direct ; le Plan B avec captures est prêt en cas de coupure. » → passer.

### Slide 16 — Bilan · *un membre, ou 1 phrase chacun* (~1 min)
- **Dit** : « Ce qui est fait : une application testée avec 38 tests, conteneurisée, livrée par CI/CD et déployée sur Kubernetes. Ce qui est compris : l'image est l'artefact, une application répliquée doit être stateless, la configuration et les secrets sont séparés. Limites connues : les fichiers uploadés ne sont pas partagés entre pods — il faudrait un stockage objet ; et un backup interne au cluster n'est qu'une première copie au sens de la règle 3-2-1. »

### Slide 17 — Merci / Questions (~10 s)
- **Dit** : « Merci, nous répondons à vos questions. »

---

## Répartition des réponses aux questions
- **Laravel — Auth/rôles/policies, tests** → Billal
- **Laravel — Voyages/formalités, validation, Eloquent** → Karim
- **Laravel — Participants/autorisation, API/Sanctum** → Nolan
- **DevOps — Docker, image, CI/CD, GHCR, tags** → Billal *(Nolan sait aussi expliquer les tags :latest/:sha)*
- **DevOps — k8s stateless : Deployment/Service/Ingress/ConfigMap-Secret, sessions** → Karim
- **DevOps — k8s stateful : StatefulSet/PVC/Job/CronJob, backup 3-2-1** → Nolan

> Règle : répondre précisément, et assumer un « je ne sais pas » plutôt que bluffer.
