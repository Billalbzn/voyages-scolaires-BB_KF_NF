<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détails du voyage : {{ $voyage->destination }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Informations Générales</h3>
                    <p class="text-gray-700"><strong>Destination :</strong> {{ $voyage->destination }}</p>
                    <p class="text-gray-700"><strong>Date de départ :</strong> {{ $voyage->date_depart }}</p>
                    <p class="text-gray-700"><strong>Date de retour :</strong> {{ $voyage->date_retour }}</p>
                    <p class="text-gray-700"><strong>Capacité maximale :</strong> {{ $voyage->places_max }} places</p>
                </div>

                <div class="flex space-x-4 border-t pt-4">
                    <a href="{{ route('voyages.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Retour à la liste
                    </a>

                    @can('update', $voyage)
                        <a href="{{ route('voyages.edit', $voyage) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                            Modifier le voyage
                        </a>
                    @endcan

                    @can('delete', $voyage)
                        <form action="{{ route('voyages.destroy', $voyage) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce voyage ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Supprimer le voyage
                            </button>
                        </form>
                    @endcan
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
