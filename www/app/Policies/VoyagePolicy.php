<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voyage;

class VoyagePolicy
{
    /**
     * Voir la liste des voyages.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['eleve', 'parent', 'enseignant', 'admin']);
    }

    /**
     * Voir le détail d'un voyage.
     */
    public function view(User $user, Voyage $voyage): bool
    {
        return in_array($user->role, ['eleve', 'parent', 'enseignant', 'admin']);
    }

    /**
     * Créer un voyage.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['enseignant', 'admin']);
    }

    /**
     * Modifier un voyage.
     */
    public function update(User $user, Voyage $voyage): bool
    {
        return $user->role === 'admin' || $user->id === $voyage->user_id;
    }

    /**
     * Supprimer un voyage.
     */
    public function delete(User $user, Voyage $voyage): bool
    {
        return $user->role === 'admin';
    }
}
