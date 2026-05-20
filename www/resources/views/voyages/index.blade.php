<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Liste des voyages') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @can('create', App\Models\Voyage::class)
                    <div class="mb-4">
                        <a href="{{ route('voyages.create') }}" class="text-blue-500 hover:text-blue-700">
                            + Nouveau voyage
                        </a>
                    </div>
                @endcan
                
                @foreach ($voyages as $voyage)
                    <div class="mb-4 border-b pb-4">
                        <strong class="text-lg">{{ $voyage->destination }}</strong><br>
                        Du {{ $voyage->date_depart }} au {{ $voyage->date_retour }}<br>
                        <a href="{{ route('voyages.show', $voyage) }}" class="text-sm text-gray-500 hover:text-gray-700">Détail</a>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</x-app-layout>
