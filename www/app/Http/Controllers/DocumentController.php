<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion des formalités (documents administratifs) d'un voyage.
 * Seul le responsable du voyage (ou un admin) peut ajouter/supprimer un document
 * -> on réutilise la VoyagePolicy::update pour centraliser la règle d'accès.
 */
class DocumentController extends Controller
{
    /** Ajouter une formalité à un voyage. */
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        Gate::authorize('update', $voyage);

        $validated = $request->validate([
            'titre'   => 'required|string|max:255',
            'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5 Mo
        ]);

        // Stockage privé (storage/app/documents), nom généré automatiquement.
        $chemin = $request->file('fichier')->store('documents');

        $voyage->documents()->create([
            'titre'          => $validated['titre'],
            'chemin_fichier' => $chemin,
        ]);

        return back()->with('success', 'Formalité ajoutée au voyage.');
    }

    /** Télécharger une formalité. */
    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document->voyage);

        abort_unless(Storage::exists($document->chemin_fichier), 404);

        return Storage::download(
            $document->chemin_fichier,
            $document->titre.'.'.pathinfo($document->chemin_fichier, PATHINFO_EXTENSION)
        );
    }

    /** Supprimer une formalité. */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('update', $document->voyage);

        Storage::delete($document->chemin_fichier);
        $document->delete();

        return back()->with('success', 'Formalité supprimée.');
    }
}
