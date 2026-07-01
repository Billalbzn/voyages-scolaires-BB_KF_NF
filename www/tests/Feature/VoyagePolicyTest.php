<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoyagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_enseignant_peut_acceder_au_formulaire_de_creation(): void
    {
        $enseignant = User::factory()->role('enseignant')->create();

        $this->actingAs($enseignant)
            ->get(route('voyages.create'))
            ->assertOk();
    }

    public function test_un_eleve_ne_peut_pas_creer_de_voyage(): void
    {
        $eleve = User::factory()->role('eleve')->create();

        $this->actingAs($eleve)
            ->get(route('voyages.create'))
            ->assertForbidden();
    }

    public function test_seul_un_admin_peut_supprimer_un_voyage(): void
    {
        $voyage = Voyage::factory()->create();

        $eleve = User::factory()->role('eleve')->create();
        $this->actingAs($eleve)->delete(route('voyages.destroy', $voyage))->assertForbidden();

        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin)->delete(route('voyages.destroy', $voyage))->assertRedirect();
        $this->assertDatabaseMissing('voyages', ['id' => $voyage->id]);
    }
}
