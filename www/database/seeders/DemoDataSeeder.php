<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Participant;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Jeu de données de démonstration (volumineux).
 * Idempotent : purge les données de démo avant de régénérer.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /** Comptes de test à préserver (créés par UserRolesSeeder). */
    private array $comptesTest = [
        'eleve@test.fr', 'parent@test.fr', 'enseignant@test.fr', 'admin@test.fr',
    ];

    private array $destinations = [
        'Rome', 'Barcelone', 'Londres', 'Berlin', 'Amsterdam', 'Lisbonne',
        'Prague', 'Vienne', 'Dublin', 'Athènes', 'Madrid', 'Édimbourg',
        'Cracovie', 'Florence', 'Bruxelles', 'Strasbourg', 'Chamonix', 'Bruges',
    ];

    private array $formalites = [
        'Passeport', 'Attestation d\'assurance', 'Fiche sanitaire',
        'Autorisation de sortie du territoire', 'Carte européenne d\'assurance maladie',
    ];

    public function run(): void
    {
        // --- Purge des données de démo (ordre : documents/participants via cascade) ---
        Voyage::query()->delete();                                  // cascade -> participants + documents
        User::whereNotIn('email', $this->comptesTest)->delete();

        // --- Utilisateurs ---
        $enseignants = User::factory()->count(6)->role('enseignant')->create();
        $parents     = User::factory()->count(20)->role('parent')->create();
        $eleves      = User::factory()->count(60)->role('eleve')->create();

        // On inclut aussi le compte enseignant de test comme responsable possible.
        $responsables = $enseignants->push(User::where('email', 'enseignant@test.fr')->first())->filter();

        $this->command->info("Utilisateurs : {$responsables->count()} enseignants, {$parents->count()} parents, {$eleves->count()} élèves.");

        // --- Voyages + participants + formalités ---
        $nbVoyages = 15;
        $totalParticipants = 0;
        $totalDocuments = 0;

        foreach (range(1, $nbVoyages) as $i) {
            $voyage = Voyage::factory()->create([
                'destination' => $this->destinations[($i - 1) % count($this->destinations)],
                'user_id'     => $responsables->random()->id,
            ]);

            // Participants : sous-ensemble distinct d'élèves (8 à 25, borné par places_max)
            $nb = min(rand(8, 25), $voyage->places_max);
            $inscrits = $eleves->random(min($nb, $eleves->count()));

            foreach ($inscrits as $eleve) {
                Participant::create([
                    'voyage_id'           => $voyage->id,
                    'user_id'             => $eleve->id,
                    // ~70 % ont l'autorisation parentale validée
                    'autorisation_parent' => rand(1, 100) <= 70,
                ]);
                $totalParticipants++;
            }

            // Formalités : 2 à 4 documents par voyage (avec un vrai fichier de démo)
            $docs = collect($this->formalites)->shuffle()->take(rand(2, 4));
            foreach ($docs as $titre) {
                $chemin = 'documents/demo-'.$voyage->id.'-'.md5($titre).'.txt';
                Storage::put($chemin, "Document de demonstration : {$titre}\nVoyage : {$voyage->destination}\n");

                Document::create([
                    'voyage_id'      => $voyage->id,
                    'titre'          => $titre,
                    'chemin_fichier' => $chemin,
                ]);
                $totalDocuments++;
            }
        }

        $this->command->info("Généré : {$nbVoyages} voyages, {$totalParticipants} inscriptions, {$totalDocuments} formalités.");
    }
}
