<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoyageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_api_refuse_l_acces_sans_token(): void
    {
        $this->getJson('/api/voyages')->assertUnauthorized();
    }

    public function test_l_api_liste_les_voyages_pour_un_utilisateur_authentifie(): void
    {
        Voyage::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->role('eleve')->create());

        $this->getJson('/api/voyages')
            ->assertOk()
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_un_enseignant_cree_un_voyage_via_l_api(): void
    {
        Sanctum::actingAs(User::factory()->role('enseignant')->create());

        $this->postJson('/api/voyages', [
            'destination' => 'Lisbonne',
            'date_depart' => now()->addWeek()->format('Y-m-d'),
            'date_retour' => now()->addWeeks(2)->format('Y-m-d'),
            'places_max'  => 25,
        ])->assertCreated();

        $this->assertDatabaseHas('voyages', ['destination' => 'Lisbonne']);
    }

    public function test_un_eleve_ne_peut_pas_creer_de_voyage_via_l_api(): void
    {
        Sanctum::actingAs(User::factory()->role('eleve')->create());

        $this->postJson('/api/voyages', [
            'destination' => 'Madrid',
            'date_depart' => now()->addWeek()->format('Y-m-d'),
            'date_retour' => now()->addWeeks(2)->format('Y-m-d'),
            'places_max'  => 25,
        ])->assertForbidden();
    }
}
