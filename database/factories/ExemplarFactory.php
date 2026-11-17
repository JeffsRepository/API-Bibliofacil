<?php

namespace Database\Factories;

use App\Models\Exemplar;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exemplar> */
class ExemplarFactory extends Factory
{
    protected $model = Exemplar::class;

    public function definition(): array
    {
        return [
            'livro_id' => Livro::factory(),
            'codigo_barras' => fake()->unique()->ean13(),
            'estado' => 'bom',
            'situacao' => 'disponivel',
        ];
    }
}
