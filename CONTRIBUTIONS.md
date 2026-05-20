# Contributions — Phase 2

Repo : https://github.com/Billalbzn/voyages-scolaires-BB_KF_NF 
Groupe : Billal, KF, NF 
Phase : 2 — Développement (CRUD, Eloquent, API REST, Rôles)

## Tableau de bord

| Bloc | Sujet | Responsable | Statut | Commits clés |
| :--- | :--- | :--- | :--- | :--- |
| A | Auth + Rôles + Policies | Billal Bouziane | 🟢 Terminé | 9899b22, da9c78c, aedcbac, 104090d, acc9b03, 5b111a1 |
| B | Modèles & Migrations | Karim Fadli | 🟢 Terminé | 3594b25, 06ca904  |
| C | CRUD Voyages | Karim Fadli | 🟢 Terminé | b430aa0, e843314, c91a5c7, d79ec3e, 240741e |
| D | CRUD Participants | Nolan Felmit | ⚪ À faire | — |
| E | API REST + tests Bruno | Nolan Felmit | ⚪ À faire | — |

*Légende statuts : 🟢 Terminé · 🟡 En cours · ⚪ À faire · 🔴 Bloqué*

---

## Auto-évaluation individuelle

### Billal Bouziane

**Blocs réalisés :** A (Authentification + Rôles + Policies)

**Ce que j'ai implémenté :**
* Installation de Laravel Breeze avec stack Blade (commit da9c78c)
* Compilation des assets Vite (Tailwind + JS) via un conteneur Node éphémère, sans installer Node sur la machine hôte
* Migration add_role_to_users_table avec colonne ENUM 4 valeurs (eleve, parent, enseignant, admin), valeur par défaut eleve (principe du moindre privilège)
* Modification du modèle User pour ajouter 'role' dans les attributs #[Fillable(...)] (syntaxe attributs PHP 8 de Laravel 13)
* Création de la VoyagePolicy avec 7 méthodes (viewAny, view, create, update, delete, restore, forceDelete)
* Règles d'accès implémentées :
  * create : enseignant ou admin uniquement (critère central de la Phase 2)
  * update : admin sur tout voyage, enseignant uniquement sur ses propres voyages
  * delete : admin uniquement (action destructive)
* Seeder UserRolesSeeder créant 4 utilisateurs de test (un par rôle), avec méthode updateOrCreate idempotente
* Branchement du seeder dans DatabaseSeeder pour qu'il s'exécute via artisan db:seed

**Difficulté principale rencontrée :**
* Doublon de migration : lors de l'édition de la migration add_role_to_users_table avec nano, j'ai utilisé un chemin sans le timestamp, ce qui a créé un nouveau fichier vide en doublon. La migration a tourné deux fois, polluant l'historique. Diagnostic via git check-ignore -v puis nettoyage manuel (suppression du fichier + DELETE FROM migrations) pour garantir un migrate:fresh propre pour les binômes (commit 104090d).
* Erreur Vite manifest : la première ouverture de /login a renvoyé une ViteManifestNotFoundException parce que les assets n'étaient pas compilés. J'ai résolu via un conteneur Node éphémère (docker run --rm node:20-alpine) plutôt que d'installer Node sur l'hôte, et j'ai commenté /public/build dans les deux .gitignore (racine + www/) pour faciliter le setup des binômes.
* Permissions root : les fichiers générés par les conteneurs Docker (migrations, assets Vite, policies) appartenaient à root, empêchant l'édition. Résolu systématiquement avec sudo chown -R $USER:$USER après chaque génération.

**Ce que j'ai appris :**
* La différence entre les propriétés Eloquent classiques (protected $fillable) et les attributs PHP 8 introduits par Laravel 13 (#[Fillable([...])]). Les deux sont équivalents fonctionnellement, Breeze installe la nouvelle syntaxe.
* Une Policy n'a pas besoin d'être liée à un modèle pour fonctionner. J'ai créé VoyagePolicy sans --model=Voyage pour respecter la séparation des blocs (le modèle Voyage est attribué au Bloc B). La Policy sera rebranchée quand KF créera le modèle.
* La commande git check-ignore -v pour diagnostiquer pourquoi un fichier est ignoré : indispensable quand plusieurs .gitignore se cumulent.
* Le pattern build agent éphémère : utiliser un conteneur Docker ponctuel (--rm) pour exécuter un outil (Node, Composer) sans le mélanger au conteneur principal de l'application.
* Le principe idempotent appliqué aux seeders avec updateOrCreate : on peut rejouer le seeder autant de fois qu'on veut sans dupliquer les données.

**Commits représentatifs :** 9899b22, da9c78c, aedcbac, 104090d, acc9b03, 5b111a1


### Karim Fadli

**Blocs réalisés :** B, C

**Ce que j'ai implémenté :**
* Création des modèles et migrations pour `Voyage` et `Participant` avec mise en place stricte des contraintes (clés étrangères, suppression en cascade).
* Définition des relations Eloquent : relations `hasMany` (un Voyage a plusieurs participants, un User a créé plusieurs voyages) et `belongsTo` dans les modèles respectifs.
* Développement complet du `VoyageController` (index, create, store, show, edit, update, destroy).
* Rebranchement de la `VoyagePolicy` (codée par Billal) sur le modèle `Voyage` fraîchement créé, et ajout de la méthode `delete` manquante.
* Création de toutes les vues associées (layout Breeze) avec intégration de la sécurité directement dans Blade via les directives `@can` pour masquer les boutons non autorisés.
* Importation de jeux d'essais via des fichiers CSV dans Adminer pour simuler des voyages et tester les affichages.

**Difficulté principale rencontrée :**
* **Le piège Tailwind CSS / Vite :** Les boutons d'action (Modifier, Supprimer) n'apparaissaient pas à l'écran. La logique de sécurité (`@can`) fonctionnait, mais sans la compilation dynamique de Vite, le texte de mes boutons était blanc sur un fond resté blanc. Contourné en appliquant des styles en ligne (inline CSS) pour garantir l'affichage sans dépendre de la compilation locale.
* **Sécurité sous Laravel 11 :** Le code initial utilisant `$this->authorize()` dans le contrôleur levait une erreur, car cette fonctionnalité native a été retirée dans Laravel 11. Problème résolu en utilisant la façade dédiée : `Gate::authorize()`.
* **Mise en cache tenace :** Lors de mes tests avec les différents rôles (Admin, Enseignant), les vues Blade ne mettaient pas toujours à jour l'affichage des boutons. J'ai dû apprendre à forcer le nettoyage de la mémoire avec `php artisan view:clear` et `optimize:clear`.
* **Erreur Eloquent `RelationNotFoundException` :** Lors du chargement d'un voyage (`$voyage->load('participants.user')`), le modèle Participant ne trouvait pas la table Users car je n'avais pas déclaré la relation `belongsTo`. Corrigé rapidement dans le modèle.

**Ce que j'ai appris :**
* Comment relier le travail de deux développeurs de manière asynchrone (prendre une Policy faite par quelqu'un d'autre et la brancher sur mes propres modèles).
* La différence de syntaxe de sécurité dans Laravel 11 (remplacement de l'autorisation native du contrôleur par la façade `Gate`).
* L'importance vitale des relations Eloquent (BelongsTo, HasMany) pour faciliter le requêtage complexe (ex: récupérer le voyage, ses participants, et les infos utilisateurs des participants en une seule ligne).
* Que l'affichage d'un bouton ne dépend pas uniquement de la logique PHP, mais aussi des outils Frontend (Vite/Tailwind) et de la gestion du cache de Laravel.

**Commits représentatifs :** "Validation complète des Blocs B et C : Modèle Voyage, CRUD complet, Vues et fix des Policies"


### Nolan Felmit

**Blocs réalisés :** D, E

**Ce que j'ai implémenté :**
(à compléter par NF)

**Difficulté principale rencontrée :**
(à compléter par NF)

**Ce que j'ai appris :**
(à compléter par NF)

**Commits représentatifs :** (à compléter par NF)
