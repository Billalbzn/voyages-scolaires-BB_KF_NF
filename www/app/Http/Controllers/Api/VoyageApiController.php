<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * VoyageApiController
 *
 * Controleur REST pour l'API /api/voyages.
 * Toutes les reponses sont au format JSON.
 * Authentification via token Sanctum (header Authorization: Bearer XXX).
 */
class VoyageApiController extends Controller
{
    /**
     * Liste paginee de tous les voyages avec leurs participants.
     * GET /api/voyages
     */
    public function index(): JsonResponse
    {
        $voyages = Voyage::with('participants')->paginate(15);

        return response()->json($voyages);
    }

    /**
     * Creer un nouveau voyage (enseignant ou admin uniquement).
     * POST /api/voyages
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Voyage::class);

        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'date_depart' => 'required|date|after:today',
            'date_retour' => 'required|date|after:date_depart',
            'places_max'  => 'required|integer|min:1|max:200',
        ]);

        $validated['user_id'] = Auth::id();
        $voyage = Voyage::create($validated);

        return response()->json($voyage, 201);
    }

    /**
     * Detail d'un voyage avec ses participants et leurs utilisateurs.
     * GET /api/voyages/{id}
     */
    public function show(Voyage $voyage): JsonResponse
    {
        return response()->json(
            $voyage->load('participants.user')
        );
    }

    /**
     * Mettre a jour un voyage (admin ou createur du voyage).
     * PUT/PATCH /api/voyages/{id}
     */
    public function update(Request $request, Voyage $voyage): JsonResponse
    {
        Gate::authorize('update', $voyage);

        $validated = $request->validate([
            'destination' => 'sometimes|required|string|max:255',
            'date_depart' => 'sometimes|required|date|after:today',
            'date_retour' => 'sometimes|required|date|after:date_depart',
            'places_max'  => 'sometimes|required|integer|min:1|max:200',
        ]);

        $voyage->update($validated);

        return response()->json($voyage->fresh());
    }

    /**
     * Supprimer un voyage (admin uniquement).
     * DELETE /api/voyages/{id}
     */
    public function destroy(Voyage $voyage): JsonResponse
    {
        Gate::authorize('delete', $voyage);

        $voyage->delete();

        return response()->json([
            'message' => 'Voyage supprime avec succes.',
        ], 200);
    }
}