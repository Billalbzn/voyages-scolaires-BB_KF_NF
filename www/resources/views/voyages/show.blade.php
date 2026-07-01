<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détails du voyage : {{ $voyage->destination }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Messages flash --}}
            @if (session('success'))
                <div style="background:#dcfce7;color:#166534;padding:10px 16px;border-radius:6px;">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div style="background:#fee2e2;color:#991b1b;padding:10px 16px;border-radius:6px;">{{ session('error') }}</div>
            @endif

            {{-- Informations générales --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Informations générales</h3>
                <p class="text-gray-700"><strong>Destination :</strong> {{ $voyage->destination }}</p>
                <p class="text-gray-700"><strong>Date de départ :</strong> {{ $voyage->date_depart->format('d/m/Y') }}</p>
                <p class="text-gray-700"><strong>Date de retour :</strong> {{ $voyage->date_retour->format('d/m/Y') }}</p>
                <p class="text-gray-700"><strong>Capacité :</strong> {{ $voyage->participants->count() }} / {{ $voyage->places_max }} places</p>

                <div class="flex space-x-4 border-t pt-4 mt-4">
                    <a href="{{ route('voyages.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Retour à la liste</a>
                    @can('update', $voyage)
                        <a href="{{ route('voyages.edit', $voyage) }}" style="background:#eab308;color:black;font-weight:bold;padding:8px 16px;border-radius:6px;text-decoration:none;">Modifier</a>
                    @endcan
                    @can('delete', $voyage)
                        <form action="{{ route('voyages.destroy', $voyage) }}" method="POST" onsubmit="return confirm('Supprimer ce voyage ?');" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#ef4444;color:white;font-weight:bold;padding:8px 16px;border-radius:6px;border:none;cursor:pointer;">Supprimer</button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Participants --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Participants</h3>

                @forelse ($voyage->participants as $participant)
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0;">
                        <span>
                            {{ $participant->user->name ?? 'Utilisateur #'.$participant->user_id }}
                            @if ($participant->autorisation_parent)
                                <span style="color:#166534;font-weight:bold;">✔ autorisé</span>
                            @else
                                <span style="color:#b45309;">⏳ en attente d'autorisation parentale</span>
                            @endif
                        </span>
                        <span style="display:flex;gap:8px;">
                            @unless ($participant->autorisation_parent)
                                @can('autoriser', $participant)
                                    <form action="{{ route('participants.autoriser', $participant) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" style="background:#22c55e;color:white;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;">Autoriser</button>
                                    </form>
                                @endcan
                            @endunless
                            @can('delete', $participant)
                                <form action="{{ route('voyages.participants.destroy', [$voyage, $participant]) }}" method="POST" onsubmit="return confirm('Désinscrire ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:#ef4444;color:white;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;">Retirer</button>
                                </form>
                            @endcan
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500">Aucun participant inscrit.</p>
                @endforelse

                {{-- Inscription (s'inscrire soi-même) --}}
                <form action="{{ route('voyages.participants.store', $voyage) }}" method="POST" style="margin-top:16px;">
                    @csrf
                    <button type="submit" style="background:#3b82f6;color:white;font-weight:bold;padding:8px 16px;border-radius:6px;border:none;cursor:pointer;">M'inscrire à ce voyage</button>
                </form>
            </div>

            {{-- Formalités / documents --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Formalités administratives</h3>

                @forelse ($voyage->documents as $document)
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0;">
                        <span>{{ $document->titre }}</span>
                        <span style="display:flex;gap:8px;">
                            <a href="{{ route('documents.download', $document) }}" style="background:#3b82f6;color:white;padding:4px 10px;border-radius:6px;text-decoration:none;">Télécharger</a>
                            @can('update', $voyage)
                                <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Supprimer ce document ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:#ef4444;color:white;padding:4px 10px;border-radius:6px;border:none;cursor:pointer;">Supprimer</button>
                                </form>
                            @endcan
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500">Aucune formalité pour l'instant.</p>
                @endforelse

                @can('update', $voyage)
                    <form action="{{ route('documents.store', $voyage) }}" method="POST" enctype="multipart/form-data" style="margin-top:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        @csrf
                        <input type="text" name="titre" placeholder="Titre (ex. Passeport)" required style="border:1px solid #ccc;border-radius:6px;padding:6px;">
                        <input type="file" name="fichier" required>
                        <button type="submit" style="background:#3b82f6;color:white;font-weight:bold;padding:8px 16px;border-radius:6px;border:none;cursor:pointer;">Ajouter</button>
                    </form>
                    @error('fichier') <p style="color:#b91c1c;">{{ $message }}</p> @enderror
                    @error('titre') <p style="color:#b91c1c;">{{ $message }}</p> @enderror
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
