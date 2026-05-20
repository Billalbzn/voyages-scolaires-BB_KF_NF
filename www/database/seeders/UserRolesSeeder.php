<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * UserRolesSeeder
 *
 * Cree 4 utilisateurs de test, un par role.
 * Permet de tester rapidement la VoyagePolicy en se connectant
 * avec chacun des roles sans avoir a creer manuellement les comptes.
 *
 * Lancement : php artisan db:seed --class=UserRolesSeeder
 * Ou via DatabaseSeeder : php artisan db:seed
 *
 * Mot de passe commun (DEV UNIQUEMENT) : password
 */
class UserRolesSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'  => 'Alice Eleve',
                'email' => 'eleve@test.fr',
                'role'  => 'eleve',
            ],
            [
                'name'  => 'Bob Parent',
                'email' => 'parent@test.fr',
                'role'  => 'parent',
            ],
            [
                'name'  => 'Claire Enseignante',
                'email' => 'enseignant@test.fr',
                'role'  => 'enseignant',
            ],
            [
                'name'  => 'David Admin',
                'email' => 'admin@test.fr',
                'role'  => 'admin',
            ],
        ];

        foreach ($users as $userData) {
            // updateOrCreate : si l'email existe deja, on met a jour le role ;
            // sinon on cree. Permet de re-jouer le seeder sans dupliquer.
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'              => $userData['name'],
                    'password'          => Hash::make('password'),
                    'role'              => $userData['role'],
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('4 utilisateurs de test crees ou mis a jour :');
        $this->command->info('  - eleve@test.fr      (role: eleve)');
        $this->command->info('  - parent@test.fr     (role: parent)');
        $this->command->info('  - enseignant@test.fr (role: enseignant)');
        $this->command->info('  - admin@test.fr      (role: admin)');
        $this->command->info('Mot de passe commun (DEV) : password');
    }
}
