<?php

namespace App\Http\Controllers;

use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VoyageController extends Controller
{
    /**
     * Afficher la liste des voyages.
     * Accessible à tout utilisateur authentifié.
     */
    public function index()
    {
        $voyages = Voyage::all();
        return view('voyages.index', compact('voyages'));
    }

    /**
     * Afficher le formulaire de création d'un voyage.
     * Réservé aux enseignants et admins (via VoyagePolicy::create).
     */
    public function create()
    {
        Gate::authorize('create', Voyage::class);
        return view('voyages.create');
    }

    /**
     * Enregistrer un nouveau voyage en base.
     * Réservé aux enseignants et admins (via VoyagePolicy::create).
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Voyage::class);

        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'date_depart' => 'required|date|after:today',
            'date_retour' => 'required|date|after:date_depart',
            'places_max'  => 'required|integer|min:1|max:200',
        ]);

        $validated['user_id'] = Auth::id();
        Voyage::create($validated);

        return redirect()->route('voyages.index')
                         ->with('success', 'Voyage créé.');
    }

    /**
     * Afficher le détail d'un voyage avec ses participants.
     * Accessible à tout utilisateur authentifié.
     */
    public function show(Voyage $voyage)
    {
        $voyage->load('participants.user', 'documents');
        return view('voyages.show', compact('voyage'));
    }

    /**
     * Afficher le formulaire d'édition d'un voyage.
     * Réservé à l'admin ou au créateur du voyage (via VoyagePolicy::update).
     */
    public function edit(Voyage $voyage)
    {
        Gate::authorize('update', $voyage);
        return view('voyages.edit', compact('voyage'));
    }

    /**
     * Mettre à jour un voyage en base.
     * Réservé à l'admin ou au créateur du voyage (via VoyagePolicy::update).
     */
    public function update(Request $request, Voyage $voyage): RedirectResponse
    {
        Gate::authorize('update', $voyage);

        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'date_depart' => 'required|date|after:today',
            'date_retour' => 'required|date|after:date_depart',
            'places_max'  => 'required|integer|min:1|max:200',
        ]);

        $voyage->update($validated);

        return redirect()->route('voyages.index')
                         ->with('success', 'Voyage mis à jour avec succès.');
    }

    /**
     * Supprimer un voyage.
     * Réservé aux admins uniquement (via VoyagePolicy::delete).
     */
    public function destroy(Voyage $voyage): RedirectResponse
    {
        Gate::authorize('delete', $voyage);
        $voyage->delete();

        return redirect()->route('voyages.index')
                         ->with('success', 'Voyage supprimé.');
    }
}
