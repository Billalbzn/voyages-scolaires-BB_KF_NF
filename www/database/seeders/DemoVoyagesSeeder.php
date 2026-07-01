<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Participant;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Jeu de démonstration SANS Faker (données en dur) -> fonctionne aussi
 * dans l'image de production (composer install --no-dev, pas de Faker).
 * Idempotent : purge la démo puis régénère. Les 4 comptes de test sont préservés.
 *
 *   php artisan db:seed --class=DemoVoyagesSeeder
 */
class DemoVoyagesSeeder extends Seeder
{
    private array $comptesTest = [
        'eleve@test.fr', 'parent@test.fr', 'enseignant@test.fr', 'admin@test.fr',
    ];

    public function run(): void
    {
        // Purge des données de démo (cascade sur participants + documents)
        Voyage::query()->delete();
        User::where('email', 'like', '%@demo.fr')->delete();

        $ens = User::firstOrCreate(
            ['email' => 'enseignant@test.fr'],
            ['name' => 'Prof Démo', 'password' => Hash::make('password'), 'role' => 'enseignant']
        );

        // Élèves de démo (idempotents)
        $prenoms = ['Lucas', 'Emma', 'Hugo', 'Léa', 'Nathan', 'Chloé', 'Louis', 'Manon', 'Jules', 'Inès', 'Tom', 'Sarah'];
        $eleves = collect();
        foreach ($prenoms as $i => $p) {
            $eleves->push(User::updateOrCreate(
                ['email' => strtolower($p).$i.'@demo.fr'],
                ['name' => $p.' Démo', 'password' => Hash::make('password'), 'role' => 'eleve']
            ));
        }

        $voyages = [
            ['Rome', '2026-09-15', '2026-09-22', 40],
            ['Barcelone', '2026-10-05', '2026-10-10', 30],
            ['Londres', '2026-11-02', '2026-11-06', 35],
            ['Berlin', '2026-12-01', '2026-12-08', 25],
            ['Amsterdam', '2027-01-12', '2027-01-16', 28],
            ['Lisbonne', '2027-02-09', '2027-02-14', 32],
            ['Prague', '2027-03-02', '2027-03-07', 30],
            ['Dublin', '2027-04-06', '2027-04-11', 26],
        ];
        $formalites = ['Passeport', 'Attestation d\'assurance', 'Fiche sanitaire'];

        $nbP = 0;
        $nbD = 0;
        foreach ($voyages as $d) {
            $v = Voyage::create([
                'destination' => $d[0],
                'date_depart' => $d[1],
                'date_retour' => $d[2],
                'places_max'  => $d[3],
                'user_id'     => $ens->id,
            ]);

            foreach ($eleves->shuffle()->take(rand(4, 10)) as $j => $e) {
                Participant::create([
                    'voyage_id'           => $v->id,
                    'user_id'             => $e->id,
                    'autorisation_parent' => ($j % 3 !== 0), // ~2/3 autorisés
                ]);
                $nbP++;
            }

            foreach (array_slice($formalites, 0, rand(1, 3)) as $titre) {
                $chemin = 'documents/demo-'.$v->id.'-'.md5($titre).'.txt';
                try {
                    Storage::put($chemin, "Document de demonstration : {$titre}\nVoyage : {$v->destination}\n");
                } catch (\Throwable $e) {
                    // stockage indisponible : on garde quand même l'enregistrement
                }
                Document::create(['voyage_id' => $v->id, 'titre' => $titre, 'chemin_fichier' => $chemin]);
                $nbD++;
            }
        }

        $this->command->info('Démo : '.count($voyages)." voyages, {$nbP} inscriptions, {$nbD} formalités.");
    }
}
