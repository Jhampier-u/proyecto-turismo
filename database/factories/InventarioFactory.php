<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventario>
 */
class InventarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_recurso' => $this->faker->sentence(3),
            'ubicacion_detallada' => $this->faker->address(),
            'descripcion' => $this->faker->paragraph(),
            'accesibilidad' => $this->faker->randomElement(['Vía asfaltada', 'Camino de tierra', 'Solo a pie']),
            'estado_conservacion' => $this->faker->randomElement(['Bueno', 'Regular', 'Malo']),
            'created_at' => now(),
        ];
    }
}
