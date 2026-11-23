<?php

namespace Database\Factories;

use App\Models\Partida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partida>
 */
class PartidaFactory extends Factory
{
    public function definition(): array
    {
        $producto = fake()->unique()->numberBetween(1, 250);

        return [
            'codigo' => "5-1-1-1-1-{$producto}",
            'grupo' => 5,
            'subgrupo' => 1,
            'clase' => 1,
            'subclase' => 1,
            'tipo' => 1,
            'producto' => $producto,
            'nivel' => 6,
            'descripcion' => 'ESCRITORIO DE MADERA '.mb_strtoupper(fake()->unique()->word()),
            'ruta' => 'MOBILIARIO Y EQUIPO DE ADMINISTRACION › MUEBLES DE OFICINA Y ESTANTERÍA › ESCRITORIO',
            'cuenta_contable' => '1.2.4.1.1',
            'seleccionable' => true,
            'activo' => true,
        ];
    }

    public function vehiculo(): static
    {
        return $this->state(fn (array $attributes) => [
            'codigo' => "5-4-1-1-9-{$attributes['producto']}",
            'subgrupo' => 4,
            'tipo' => 9,
            'descripcion' => 'CAMIONETA CHASIS '.$attributes['producto'],
            'ruta' => 'VEHICULOS Y EQUIPO DE TRANSPORTE › AUTOMOVILES Y EQUIPO TERRESTRE',
            'cuenta_contable' => null,
        ]);
    }
}
