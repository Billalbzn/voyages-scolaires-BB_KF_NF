<?php

namespace App\Policies;

use App\Models\User;

/**
 * VoyagePolicy
 *
 * Centralise les regles d'autorisation pour les voyages scolaires.
 * Les voyages eux-memes seront definis au Bloc B (modele Voyage).
 *
 * Resume des roles :
 * - eleve      : ne peut que consulter (viewAny, view)
 * - parent     : ne peut que consulter (viewAny, view)
 * - enseignant : peut creer, modifier ses propres voyages, mais pas supprimer
 * - admin      : peut tout faire (create, update, delete sur tout voyage)
 */
class VoyagePolicy
{
    /**
     * Voir la liste des voyages.
     * Toute personne authentifiee peut consulter la liste.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['eleve', 'parent', 'enseignant', 'admin']);
    }

    /**
     * Voir le detail d'un voyage.
     * Toute personne authentifiee peut consulter un voyage.
     */
    public function view(User $user, $voyage = null): bool
    {
        return in_array($user->role, ['eleve', 'parent', 'enseignant', 'admin']);
    }

    /**
     * Creer un voyage.
     * Reserve aux enseignants et admins.
     * C'est le critere central de la Phase 2 : un eleve ne peut PAS creer.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['enseignant', 'admin']);
    }

    /**
     * Modifier un voyage.
     * - Admin : peut modifier n'importe quel voyage
     * - Enseignant : peut modifier seulement les voyages qu'il a crees
     * - Autres : interdit
     *
     * Le parametre $voyage n'est pas type-hinte pour rester compatible
     * tant que le modele Voyage n'existe pas (Bloc B en cours).
     */
    public function update(User $user, $voyage = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'enseignant' && $voyage !== null) {
            return $user->id === $voyage->user_id;
        }

        return false;
    }

    /**
     * Supprimer un voyage.
     * Reserve aux admins uniquement (action destructive).
     */
    public function delete(User $user, $voyage = null): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Restaurer un voyage soft-deleted.
     * Reserve aux admins (sera utile si on active SoftDeletes plus tard).
     */
    public function restore(User $user, $voyage = null): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Supprimer definitivement un voyage soft-deleted.
     * Reserve aux admins uniquement.
     */
    public function forceDelete(User $user, $voyage = null): bool
    {
        return $user->role === 'admin';
    }
}
