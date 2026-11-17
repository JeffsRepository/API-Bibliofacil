<?php

namespace Database\Factories;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(85) 9####-####'),
            'tipo' => TipoUsuario::Cliente,
            'password' => null,
            'cpf' => fake()->unique()->numerify('###.###.###-##'),
            'endereco' => fake()->streetAddress(),
        ];
    }

    public function cliente(): static
    {
        return $this->state(fn () => ['tipo' => TipoUsuario::Cliente]);
    }

    public function funcionario(): static
    {
        return $this->equipe(TipoUsuario::Funcionario, 'Atendente');
    }

    public function admin(): static
    {
        return $this->equipe(TipoUsuario::Admin, 'Administrador');
    }

    private function equipe(TipoUsuario $tipo, string $cargo): static
    {
        return $this->state(fn () => [
            'tipo' => $tipo,
            'password' => 'senha123',
            'cpf' => null,
            'endereco' => null,
            'matricula' => fake()->unique()->numerify('MAT-####'),
            'cargo' => $cargo,
        ]);
    }
}
