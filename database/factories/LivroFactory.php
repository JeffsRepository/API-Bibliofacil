<?php

namespace Database\Factories;

use App\Models\Livro;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Livro> */
class LivroFactory extends Factory
{
    protected $model = Livro::class;

    public function definition(): array
    {
        return [
            'isbn' => fake()->unique()->isbn13(),
            'titulo' => fake()->sentence(3),
            'autor' => fake()->name(),
            'preco_venda' => fake()->randomFloat(2, 15, 90),
            'preco_emprestimo' => fake()->randomFloat(2, 2, 10),
        ];
    }
}
