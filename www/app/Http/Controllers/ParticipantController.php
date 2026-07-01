<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ParticipantController extends Controller
{
    /**
     * Inscrire un participant à un voyage.
     * Par défaut on inscrit l'utilisateur connecté ; un enseignant/admin
     * peut inscrire un autre élève en passant user_id.
     */
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? Auth::id();

        // Empêche la double inscription au même voyage.
        $exists = $voyage->participants()->where('user_id', $userId)->exists();
        if ($exists) {
            return back()->with('error', 'Cet utilisateur est déjà inscrit à ce voyage.');
        }

        // Contrôle des places disponibles.
        if ($voyage->participants()->count() >= $voyage->places_max) {
            return back()->with('error', 'Le voyage est complet.');
        }

        $voyage->participants()->create([
            'user_id'             => $userId,
            'autorisation_parent' => false,
        ]);

        return back()->with('success', 'Inscription enregistrée. En attente de l\'autorisation parentale.');
    }

    /**
     * Valider l'autorisation parentale (parent ou admin uniquement).
     * PATCH /participants/{participant}/autoriser
     */
    public function autoriser(Participant $participant): RedirectResponse
    {
        Gate::authorize('autoriser', $participant);

        $participant->update(['autorisation_parent' => true]);

        return back()->with('success', 'Autorisation parentale enregistrée.');
    }

    /**
     * Désinscrire un participant d'un voyage.
     */
    public function destroy(Voyage $voyage, Participant $participant): RedirectResponse
    {
        Gate::authorize('delete', $participant);

        $participant->delete();

        return back()->with('success', 'Participation annulée.');
    }
}
