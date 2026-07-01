<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'voyage_id'           => Voyage::factory(),
            'user_id'             => User::factory()->role('eleve'),
            'autorisation_parent' => false,
        ];
    }

    /** Participant dont l'autorisation parentale est validée. */
    public function autorise(): static
    {
        return $this->state(fn (array $attributes) => ['autorisation_parent' => true]);
    }
}
