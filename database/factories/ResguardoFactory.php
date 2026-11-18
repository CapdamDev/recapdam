<?php

namespace Database\Factories;

use App\Models\Resguardo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resguardo>
 */
class ResguardoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_id' => \App\Models\Persona::factory(),
            'equipo_id' => \App\Models\Equipo::factory(),
            'fecha_asignacion' => fake()->dateTimeBetween('-1 year'),
            'fecha_devolucion' => null,
        ];
    }
}
