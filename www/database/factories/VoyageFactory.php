<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voyage>
 */
class VoyageFactory extends Factory
{
    protected $model = Voyage::class;

    public function definition(): array
    {
        $depart = fake()->dateTimeBetween('+1 week', '+3 months');
        $retour = (clone $depart)->modify('+'.fake()->numberBetween(3, 14).' days');

        return [
            'destination' => fake()->city(),
            'date_depart' => $depart->format('Y-m-d'),
            'date_retour' => $retour->format('Y-m-d'),
            'places_max'  => fake()->numberBetween(10, 60),
            'user_id'     => User::factory()->role('enseignant'),
        ];
    }
}
