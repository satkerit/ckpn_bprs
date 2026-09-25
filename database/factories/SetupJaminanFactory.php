<?php

namespace Database\Factories;

use App\Models\SetupJaminan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SetupJaminan>
 */
class SetupJaminanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kdjam' => fake()->unique()->lexify('JAM???'),
            'ket' => fake()->sentence(3),
            'bobot' => fake()->randomFloat(2, 10, 100),
        ];
    }
}
