# Contributions — Phase 2

> Repo : https://github.com/Billalbzn/voyages-scolaires-BB_KF_NF
> Groupe : Billal, KF, NF
> Phase : 2 — Développement (CRUD, Eloquent, API REST, Rôles)

## Tableau de bord

| Bloc | Sujet                       | Responsable | Statut    | Commits clés |
|------|-----------------------------|-------------|-----------|--------------|
| A    | Auth + Rôles + Policies     | BillalBouziane      | 🟢 Terminé | 9899b22, da9c78c, aedcbac, 104090d, acc9b03, 5b111a1 |
| B    | Modèles & Migrations        | KarimFadli          | ⚪ À faire | —            |
| C    | CRUD Voyages                | KarimFadli          | ⚪ À faire | —            |
| D    | CRUD Participants           | NolanFelmit         | ⚪ À faire | —            |
| E    | API REST + tests Bruno      | NolanFelmit         | ⚪ À faire | —            |

**Légende statuts :** 🟢 Terminé · 🟡 En cours · ⚪ À faire · 🔴 Bloqué

---

## Auto-évaluation individuelle

### Billal Bouziane
- **Blocs réalisés** : A (Authentification + Rôles + Policies)

- **Ce que j'ai implémenté** :
  - Installation de Laravel Breeze avec stack Blade (commit `da9c78c`)
  - Compilation des assets Vite (Tailwind + JS) via un conteneur Node éphémère, sans installer Node sur la machine hôte
  - Migration `add_role_to_users_table` avec colonne ENUM 4 valeurs (`eleve`, `parent`, `enseignant`, `admin`), valeur par défaut `eleve` (principe du moindre privilège)
  - Modification du modèle `User` pour ajouter `'role'` dans les attributs `#[Fillable(...)]` (syntaxe attributs PHP 8 de Laravel 13)
  - Création de la `VoyagePolicy` avec 7 méthodes (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`)
  - Règles d'accès implémentées :
    - `create` : enseignant ou admin uniquement (critère central de la Phase 2)
    - `update` : admin sur tout voyage, enseignant uniquement sur ses propres voyages
    - `delete` : admin uniquement (action destructive)
  - Seeder `UserRolesSeeder` créant 4 utilisateurs de test (un par rôle), avec méthode `updateOrCreate` idempotente
  - Branchement du seeder dans `DatabaseSeeder` pour qu'il s'exécute via `artisan db:seed`

- **Difficulté principale rencontrée** :
  - **Doublon de migration** : lors de l'édition de la migration `add_role_to_users_table` avec `nano`, j'ai utilisé un chemin sans le timestamp, ce qui a créé un nouveau fichier vide en doublon. La migration a tourné deux fois, polluant l'historique. Diagnostic via `git check-ignore -v` puis nettoyage manuel (suppression du fichier + `DELETE FROM migrations`) pour garantir un `migrate:fresh` propre pour les binômes (commit `104090d`).
  - **Erreur Vite manifest** : la première ouverture de `/login` a renvoyé une `ViteManifestNotFoundException` parce que les assets n'étaient pas compilés. J'ai résolu via un conteneur Node éphémère (`docker run --rm node:20-alpine`) plutôt que d'installer Node sur l'hôte, et j'ai commenté `/public/build` dans les deux `.gitignore` (racine + `www/`) pour faciliter le setup des binômes.
  - **Permissions root** : les fichiers générés par les conteneurs Docker (migrations, assets Vite, policies) appartenaient à `root`, empêchant l'édition. Résolu systématiquement avec `sudo chown -R $USER:$USER` après chaque génération.

- **Ce que j'ai appris** :
  - La différence entre les **propriétés Eloquent classiques** (`protected $fillable`) et les **attributs PHP 8** introduits par Laravel 13 (`#[Fillable([...])]`). Les deux sont équivalents fonctionnellement, Breeze installe la nouvelle syntaxe.
  - Une **Policy n'a pas besoin d'être liée à un modèle** pour fonctionner. J'ai créé `VoyagePolicy` sans `--model=Voyage` pour respecter la séparation des blocs (le modèle `Voyage` est attribué au Bloc B). La Policy sera rebranchée quand KF créera le modèle.
  - La commande **`git check-ignore -v`** pour diagnostiquer pourquoi un fichier est ignoré : indispensable quand plusieurs `.gitignore` se cumulent.
  - Le pattern **build agent éphémère** : utiliser un conteneur Docker ponctuel (`--rm`) pour exécuter un outil (Node, Composer) sans le mélanger au conteneur principal de l'application.
  - Le principe **idempotent** appliqué aux seeders avec `updateOrCreate` : on peut rejouer le seeder autant de fois qu'on veut sans dupliquer les données.

- **Commits représentatifs** : `9899b22`, `da9c78c`, `aedcbac`, `104090d`, `acc9b03`, `5b111a1`
---

### KarimFadli
- **Blocs réalisés** : B, C
- **Ce que j'ai implémenté** :
  - _(à compléter par KF)_
- **Difficulté principale rencontrée** :
  - _(à compléter par KF)_
- **Ce que j'ai appris** :
  - _(à compléter par KF)_
- **Commits représentatifs** : _(à compléter par KF)_

---

### NolanFelmit
- **Blocs réalisés** : D, E
- **Ce que j'ai implémenté** :
  - _(à compléter par NF)_
- **Difficulté principale rencontrée** :
  - _(à compléter par NF)_
- **Ce que j'ai appris** :
  - _(à compléter par NF)_
- **Commits représentatifs** : _(à compléter par NF)_
