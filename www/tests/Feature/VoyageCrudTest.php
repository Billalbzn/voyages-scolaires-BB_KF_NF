<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoyageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visiteur_non_authentifie_est_redirige_vers_login(): void
    {
        $this->get(route('voyages.index'))->assertRedirect(route('login'));
    }

    public function test_un_enseignant_cree_un_voyage(): void
    {
        $enseignant = User::factory()->role('enseignant')->create();

        $response = $this->actingAs($enseignant)->post(route('voyages.store'), [
            'destination' => 'Rome',
            'date_depart' => now()->addWeek()->format('Y-m-d'),
            'date_retour' => now()->addWeeks(2)->format('Y-m-d'),
            'places_max'  => 30,
        ]);

        $response->assertRedirect(route('voyages.index'));
        $this->assertDatabaseHas('voyages', [
            'destination' => 'Rome',
            'user_id'     => $enseignant->id,
        ]);
    }

    public function test_la_validation_refuse_une_date_de_retour_avant_le_depart(): void
    {
        $enseignant = User::factory()->role('enseignant')->create();

        $this->actingAs($enseignant)->post(route('voyages.store'), [
            'destination' => 'Berlin',
            'date_depart' => now()->addWeeks(2)->format('Y-m-d'),
            'date_retour' => now()->addWeek()->format('Y-m-d'),
            'places_max'  => 20,
        ])->assertSessionHasErrors('date_retour');
    }
}
