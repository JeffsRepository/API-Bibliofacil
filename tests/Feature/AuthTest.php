<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_faz_login_e_recebe_token(): void
    {
        Usuario::factory()->funcionario()->create(['email' => 'func@teste.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'func@teste.com', 'password' => 'senha123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'usuario' => ['id', 'nome', 'tipo']]);
    }

    public function test_cliente_nao_consegue_fazer_login(): void
    {
        Usuario::factory()->cliente()->create(['email' => 'cli@teste.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'cli@teste.com', 'password' => 'qualquer'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_rotas_exigem_autenticacao(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_me_retorna_usuario_logado(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.tipo', 'admin');
    }
}
