<?php

namespace Tests\Feature;

use App\Models\Emprestimo;
use App\Models\Exemplar;
use App\Models\Livro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmprestimoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $funcionario;

    private Usuario $cliente;

    private Livro $livro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->funcionario = Usuario::factory()->funcionario()->create();
        $this->cliente = Usuario::factory()->cliente()->create();
        $this->livro = Livro::factory()->create(['preco_emprestimo' => 3.50]);
        Exemplar::factory()->for($this->livro)->create();
    }

    public function test_registra_emprestimo_copia_preco_e_baixa_estoque(): void
    {
        $resp = $this->actingAs($this->funcionario, 'sanctum')->postJson('/api/v1/emprestimos', [
            'cliente_id' => $this->cliente->id,
            'livro_id' => $this->livro->id,
            'data_devolucao_prevista' => now()->addDays(7)->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.valor_cobrado', 3.5)
            ->assertJsonPath('data.situacao', 'ativo');

        $this->assertSame(0, $this->livro->exemplaresDisponiveis()->count());

        // devolução: exemplar volta a ficar disponível
        $this->actingAs($this->funcionario, 'sanctum')
            ->patchJson('/api/v1/emprestimos/'.$resp->json('data.id').'/devolucao')
            ->assertOk()
            ->assertJsonPath('data.situacao', 'devolvido');

        $this->assertSame(1, $this->livro->exemplaresDisponiveis()->count());
    }

    public function test_nao_empresta_sem_exemplar_disponivel(): void
    {
        $this->livro->exemplares()->update(['situacao' => 'emprestado']);

        $this->actingAs($this->funcionario, 'sanctum')->postJson('/api/v1/emprestimos', [
            'cliente_id' => $this->cliente->id,
            'livro_id' => $this->livro->id,
            'data_devolucao_prevista' => now()->addDays(7)->toDateString(),
        ])->assertUnprocessable();
    }

    public function test_cliente_com_atraso_fica_bloqueado_e_aparece_nas_pendencias(): void
    {
        $outro = Exemplar::factory()->for($this->livro)->create(['situacao' => 'emprestado']);
        Emprestimo::create([
            'cliente_id' => $this->cliente->id, 'funcionario_id' => $this->funcionario->id, 'exemplar_id' => $outro->id,
            'data_emprestimo' => now()->subDays(20), 'data_devolucao_prevista' => now()->subDays(6), 'valor_cobrado' => 3,
        ]);

        $this->actingAs($this->funcionario, 'sanctum')
            ->getJson("/api/v1/clientes/{$this->cliente->id}/pendencias")
            ->assertOk()
            ->assertJsonPath('data.total_atrasados', 1)
            ->assertJsonPath('data.pode_emprestar', false);

        $this->actingAs($this->funcionario, 'sanctum')->postJson('/api/v1/emprestimos', [
            'cliente_id' => $this->cliente->id,
            'livro_id' => $this->livro->id,
            'data_devolucao_prevista' => now()->addDays(7)->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');

        $this->actingAs($this->funcionario, 'sanctum')
            ->getJson('/api/v1/emprestimos/atrasados')
            ->assertOk()
            ->assertJsonPath('data.0.dias_atraso', 6)
            ->assertJsonPath('meta.contadores.atrasados', 1);
    }

    public function test_leitor_precisa_ser_cliente(): void
    {
        $this->actingAs($this->funcionario, 'sanctum')->postJson('/api/v1/emprestimos', [
            'cliente_id' => $this->funcionario->id,
            'livro_id' => $this->livro->id,
            'data_devolucao_prevista' => now()->addDays(7)->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
    }
}
