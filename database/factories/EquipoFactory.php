<?php

namespace Database\Factories;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria' => fake()->randomElement(['Laptop', 'PC', 'Monitor', 'Celular', 'Impresora']),
            'numero_inventario' => fake()->unique()->numerify('#####'),
            'marca' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'Samsung']),
            'modelo' => fake()->bothify('??-####'),
            'numero_serie' => fake()->unique()->bothify('SN-########'),
        ];
    }

    public function vehiculo(): static
    {
        return $this->state(fn () => [
            'categoria' => \App\Models\Equipo::CATEGORIA_VEHICULO,
            'marca' => 'NISSAN',
            'modelo' => 'ESTACA NP300',
            'anio' => 2023,
            'placa' => fake()->unique()->bothify('??####?'),
            'numero_economico' => fake()->numerify('##'),
            'cilindros' => 4,
            'color' => 'BLANCO',
            'combustible' => 'GASOLINA',
        ]);
    }
}
