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
    
    // Tu pourras remplir show(), edit(), update() et destroy() ensuite !
}
