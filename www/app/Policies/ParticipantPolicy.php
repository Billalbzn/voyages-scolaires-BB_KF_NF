<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    /**
     * Valider l'autorisation parentale.
     * Réservé aux parents et aux admins (un élève ne s'auto-autorise pas).
     */
    public function autoriser(User $user, Participant $participant): bool
    {
        return in_array($user->role, ['parent', 'admin'], true);
    }

    /**
     * Retirer un participant d'un voyage.
     * Le responsable du voyage, un admin, ou le participant lui-même.
     */
    public function delete(User $user, Participant $participant): bool
    {
        return $user->role === 'admin'
            || $user->id === $participant->user_id
            || $user->id === $participant->voyage->user_id;
    }
}
