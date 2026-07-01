<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_s_inscrire_a_un_voyage(): void
    {
        $voyage = Voyage::factory()->create();
        $eleve = User::factory()->role('eleve')->create();

        $this->actingAs($eleve)
            ->post(route('voyages.participants.store', $voyage))
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'voyage_id'           => $voyage->id,
            'user_id'             => $eleve->id,
            'autorisation_parent' => false,
        ]);
    }

    public function test_un_parent_peut_valider_l_autorisation_parentale(): void
    {
        $participant = Participant::factory()->create();
        $parent = User::factory()->role('parent')->create();

        $this->actingAs($parent)
            ->patch(route('participants.autoriser', $participant))
            ->assertRedirect();

        $this->assertTrue($participant->fresh()->autorisation_parent);
    }

    public function test_un_eleve_ne_peut_pas_valider_l_autorisation(): void
    {
        $participant = Participant::factory()->create();
        $eleve = User::factory()->role('eleve')->create();

        $this->actingAs($eleve)
            ->patch(route('participants.autoriser', $participant))
            ->assertForbidden();

        $this->assertFalse($participant->fresh()->autorisation_parent);
    }
}
