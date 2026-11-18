<?php

namespace Database\Factories;

use App\Models\Evidencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidencia>
 */
class EvidenciaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resguardo_id' => \App\Models\Resguardo::factory(),
            'archivo' => 'evidencias/'.fake()->uuid().'.jpg',
            'archivo_nombre_original' => 'foto.jpg',
            'numero_serie' => fake()->bothify('SN-########'),
            'nombre_persona' => fake()->name(),
        ];
    }
}
