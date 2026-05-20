<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantController extends Controller
{
    public function store(Request $request, $voyageId)
    {
        Participant::create([
            'voyage_id' => $voyageId,
            'user_id' => Auth::id(),
            'autorisation_parent' => 0,
        ]);

        return back()->with('status', 'Inscription enregistrée. En attente de l\'autorisation parentale.');
    }

    public function update(Request $request, $voyageId, $participantId)
    {
        $participant = Participant::findOrFail($participantId);

        $participant->update([
            'autorisation_parent' => $request->has('autorisation_parent') ? 1 : 0
        ]);

        return back()->with('status', 'Statut de l\'autorisation mis à jour.');
    }

    public function destroy($voyageId, $participantId)
    {
        $participant = Participant::findOrFail($participantId);
        $participant->delete();

        return back()->with('status', 'Participation annulée.');
    }
}