<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_nao_exclui_livro(): void
    {
        $livro = Livro::factory()->create();

        $this->actingAs(Usuario::factory()->funcionario()->create(), 'sanctum')
            ->deleteJson("/api/v1/livros/{$livro->id}")
            ->assertForbidden();
    }

    public function test_funcionario_nao_acessa_relatorios(): void
    {
        $this->actingAs(Usuario::factory()->funcionario()->create(), 'sanctum')
            ->getJson('/api/v1/relatorios')
            ->assertForbidden();
    }

    public function test_funcionario_nao_cria_admin_mas_cria_cliente(): void
    {
        $func = Usuario::factory()->funcionario()->create();

        $this->actingAs($func, 'sanctum')->postJson('/api/v1/usuarios', [
            'nome' => 'Hacker', 'email' => 'h@t.com', 'tipo' => 'admin',
            'password' => 'senha1234', 'password_confirmation' => 'senha1234',
        ])->assertForbidden();

        $this->actingAs($func, 'sanctum')->postJson('/api/v1/usuarios', [
            'nome' => 'Leitor Novo', 'email' => 'l@t.com', 'tipo' => 'cliente',
        ])->assertCreated()->assertJsonPath('data.tipo', 'cliente');
    }

    public function test_admin_cria_outro_admin_com_senha_obrigatoria(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/usuarios', [
            'nome' => 'Admin 2', 'email' => 'a2@t.com', 'tipo' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/usuarios', [
            'nome' => 'Admin 2', 'email' => 'a2@t.com', 'tipo' => 'admin',
            'password' => 'senha1234', 'password_confirmation' => 'senha1234',
        ])->assertCreated();
    }

    public function test_cadastro_de_livro_sem_quantidade_e_com_um_exemplar(): void
    {
        $this->actingAs(Usuario::factory()->admin()->create(), 'sanctum')->postJson('/api/v1/livros', [
            'titulo' => 'Iracema', 'autor' => 'José de Alencar', 'isbn' => '978-85-0000-001',
            'preco_venda' => 30, 'preco_emprestimo' => 4,
            'exemplar' => ['codigo_barras' => 'CB-001', 'estado' => 'novo'],
        ])->assertCreated()
            ->assertJsonPath('data.quantidade_disponivel', 1)
            ->assertJsonPath('data.exemplares.0.codigo_barras', 'CB-001');
    }
}
