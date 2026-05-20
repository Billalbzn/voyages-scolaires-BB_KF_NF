<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier le voyage : {{ $voyage->destination }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('voyages.update', $voyage) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Destination</label>
                        <input type="text" name="destination" value="{{ old('destination', $voyage->destination) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date de départ</label>
                        <input type="date" name="date_depart" value="{{ old('date_depart', $voyage->date_depart) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date de retour</label>
                        <input type="date" name="date_retour" value="{{ old('date_retour', $voyage->date_retour) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Places maximum</label>
                        <input type="number" name="places_max" min="1" max="200" value="{{ old('places_max', $voyage->places_max) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="flex items-center space-x-4">
                        <button type="submit" style="background-color: #3b82f6; color: white; font-weight: bold; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
                            Enregistrer les modifications
                        </button>
                        <a href="{{ route('voyages.show', $voyage) }}" style="background-color: #6b7280; color: white; font-weight: bold; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                            Annuler
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
