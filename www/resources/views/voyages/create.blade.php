@extends('layouts.app')

@section('content')
    <h1>Créer un nouveau voyage</h1>

    <form action="{{ route('voyages.store') }}" method="POST">
        @csrf
        <div>
            <label>Destination</label>
            <input type="text" name="destination" required>
        </div>
        <div>
            <label>Date de départ</label>
            <input type="date" name="date_depart" required>
        </div>
        <div>
            <label>Date de retour</label>
            <input type="date" name="date_retour" required>
        </div>
        <div>
            <label>Places max</label>
            <input type="number" name="places_max" min="1" max="200" required>
        </div>
        <button type="submit">Enregistrer le voyage</button>
    </form>
@endsection
