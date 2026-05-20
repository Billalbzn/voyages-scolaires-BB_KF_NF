<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le champ 'role' a la table users.
     * Permet de differencier eleve, parent, enseignant et admin
     * pour la gestion des autorisations (Policies, Gates).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['eleve', 'parent', 'enseignant', 'admin'])
                  ->default('eleve')
                  ->after('email');
        });
    }

    /**
     * Rollback : supprime le champ 'role'.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
