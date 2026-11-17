<?php

namespace Tests\Feature;

use App\Models\Exemplar;
use App\Models\FormaPagamento;
use App\Models\Livro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantidade_2_gera_dois_itens_com_exemplares_diferentes(): void
    {
        $func = Usuario::factory()->funcionario()->create();
        $cliente = Usuario::factory()->cliente()->create();
        $pix = FormaPagamento::create(['nome' => 'Pix', 'slug' => 'pix']);
        $livro = Livro::factory()->create(['preco_venda' => 10]);
        Exemplar::factory()->for($livro)->count(3)->create();

        $resp = $this->actingAs($func, 'sanctum')->postJson('/api/v1/vendas', [
            'cliente_id' => $cliente->id,
            'forma_pagamento_id' => $pix->id,
            'desconto' => 5,
            'itens' => [['livro_id' => $livro->id, 'quantidade' => 2]],
        ])->assertCreated()
            ->assertJsonPath('data.subtotal', 20)
            ->assertJsonPath('data.total', 15)
            ->assertJsonPath('data.itens.0.quantidade', 2);

        $this->assertCount(2, $resp->json('data.itens.0.exemplares'));
        $this->assertDatabaseCount('itens_venda', 2);
        $this->assertSame(1, $livro->exemplaresDisponiveis()->count());
    }

    public function test_desconto_maior_que_subtotal_e_rejeitado(): void
    {
        $livro = Livro::factory()->create(['preco_venda' => 10]);
        Exemplar::factory()->for($livro)->create();

        $this->actingAs(Usuario::factory()->funcionario()->create(), 'sanctum')
            ->postJson('/api/v1/vendas/previa', ['desconto' => 50, 'itens' => [['livro_id' => $livro->id, 'quantidade' => 1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('desconto');
    }

    public function test_relatorio_vendas_por_pagamento_soma_total(): void
    {
        $admin = Usuario::factory()->admin()->create();
        FormaPagamento::create(['nome' => 'Dinheiro', 'slug' => 'dinheiro']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/relatorios/vendas-por-pagamento?mes='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['periodo', 'formas', 'total_geral' => ['vendas', 'total']]]);
    }
}
