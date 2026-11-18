<?php

namespace Database\Factories;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'tipo' => \App\Enums\TipoPersona::Empleado,
            'numero_empleado' => fake()->unique()->numerify('EMP-#####'),
            'departamento' => fake()->randomElement(['Sistemas', 'Comercial', 'Operación', 'Administración']),
            'email' => fake()->safeEmail(),
            'telefono' => fake()->numerify('312#######'),
        ];
    }
}
