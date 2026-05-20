<?php

namespace App\Http\Controllers;

use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VoyageController extends Controller
{
    public function index()
    {
        $voyages = Voyage::all();
        return view('voyages.index', compact('voyages'));
    }

    public function create()
    {
        return view('voyages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'date_depart' => 'required|date|after:today',
            'date_retour' => 'required|date|after:date_depart',
            'places_max' => 'required|integer|min:1|max:200',
        ]);

        $validated['user_id'] = Auth::id();
        
        Voyage::create($validated);

        return redirect()->route('voyages.index')
                         ->with('success', 'Voyage créé.');
    }
    public function show(Voyage $voyage)
{
    // On charge également les participants et les utilisateurs liés pour l'affichage
    $voyage->load('participants.user');
    
    return view('voyages.show', compact('voyage'));
}

/**
 * Afficher le formulaire d'édition d'un voyage.
 */
public function edit(Voyage $voyage)
{
    // Sécurité : Vérifie via la VoyagePolicy si l'utilisateur a le droit de modifier
    $this->authorize('update', $voyage);

    return view('voyages.edit', compact('voyage'));
}

/**
 * Mettre à jour le voyage dans la base de données.
 */
public function update(Request $request, Voyage $voyage): RedirectResponse
{
    // Sécurité : Vérifie les droits d'accès
    $this->authorize('update', $voyage);

    // Validation stricte des données reçues (règle du PDF)
    $validated = $request->validate([
        'destination' => 'required|string|max:255',
        'date_depart' => 'required|date|after:today',
        'date_retour' => 'required|date|after:date_depart',
        'places_max' => 'required|integer|min:1|max:200',
    ]);

    $voyage->update($validated);

    return redirect()->route('voyages.index')
                     ->with('success', 'Voyage mis à jour avec succès.');
}

/**
 * Supprimer un voyage de la base de données.
 */
public function destroy(Voyage $voyage): RedirectResponse
{
    // Sécurité : Vérifie via la VoyagePolicy (Seul l'admin peut supprimer d'après le PDF)
    $this->authorize('delete', $voyage);

    $voyage->delete();

    return redirect()->route('voyages.index')
                     ->with('success', 'Voyage supprimé.');
}
}
