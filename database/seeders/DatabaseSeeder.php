<?php

namespace Database\Seeders;

use App\Enums\SituacaoExemplar;
use App\Models\Emprestimo;
use App\Models\Exemplar;
use App\Models\FormaPagamento;
use App\Models\ItemVenda;
use App\Models\Livro;
use App\Models\Usuario;
use App\Models\Venda;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Popula o banco com os mesmos dados mostrados nas telas do protótipo.
 * Logins: ana.silva@example.com / senha123 (admin) · roberto.faria@example.com / senha123 (funcionário)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Equipe ----------
        $admin = Usuario::factory()->admin()->create([
            'nome' => 'Ana Silva', 'email' => 'ana.silva@example.com', 'telefone' => '(85) 90000-0001', 'matricula' => 'MAT-0001',
        ]);
        $funcionario = Usuario::factory()->funcionario()->create([
            'nome' => 'Roberto Faria', 'email' => 'roberto.faria@example.com', 'telefone' => '(85) 90000-0002', 'matricula' => 'MAT-0002',
        ]);

        // ---------- Clientes ----------
        $nomes = ['Ana Souza', 'Carlos Lima', 'Marina Alves', 'Pedro Rocha', 'Julia Ramos', 'Rafael Costa', 'Bruno Dias', 'Lívia Matos', 'Tiago Neri'];
        $clientes = collect($nomes)->mapWithKeys(fn ($nome, $i) => [$nome => Usuario::factory()->cliente()->create([
            'nome' => $nome,
            'email' => Str::slug($nome, '.').'@example.com',
            'telefone' => sprintf('(85) 90000-%04d', $i + 3),
        ])]);
        Usuario::factory()->cliente()->count(10)->create();

        // ---------- Formas de pagamento (cadastro prévio) ----------
        $formas = collect([
            ['nome' => 'Cartão de Crédito', 'slug' => 'cartao_credito'],
            ['nome' => 'Cartão de Débito', 'slug' => 'cartao_debito'],
            ['nome' => 'Pix', 'slug' => 'pix'],
            ['nome' => 'Dinheiro', 'slug' => 'dinheiro'],
        ])->mapWithKeys(fn ($f) => [$f['slug'] => FormaPagamento::create($f)]);

        // ---------- Acervo: título + exemplares (um código de barras por unidade) ----------
        $acervo = [
            ['Dom Casmurro', 'Machado de Assis', '978-85-0001-001', 10.00, 3.00, ['bom', 'bom', 'bom', 'novo', 'bom']],
            ['Memórias Póstumas de Brás Cubas', 'Machado de Assis', '978-85-0001-002', 25.00, 4.00, ['desgastado']],
            ['Grande Sertão: Veredas', 'João Guimarães Rosa', '978-85-0001-003', 45.00, 5.00, ['novo', 'novo', 'bom']],
            ['Vidas Secas', 'Graciliano Ramos', '978-85-0001-004', 22.00, 3.00, ['bom', 'bom', 'bom', 'bom']],
            ['O Cortiço', 'Aluísio Azevedo', '978-85-0001-005', 20.00, 3.00, ['danificado']],
            ['Quincas Borba', 'Machado de Assis', '978-85-0001-006', 24.00, 3.50, ['bom', 'bom']],
            ['Capitães da Areia', 'Jorge Amado', '978-85-0001-007', 18.00, 3.00, ['bom', 'novo', 'bom']],
            ['A Hora da Estrela', 'Clarice Lispector', '978-85-0001-008', 28.00, 3.50, ['bom', 'bom']],
            ['O Nome da Rosa', 'Umberto Eco', '978-85-0001-009', 20.00, 4.00, ['novo', 'novo', 'bom']],
        ];

        $livros = [];
        foreach ($acervo as $n => [$titulo, $autor, $isbn, $venda, $emprestimo, $estados]) {
            $livro = Livro::create(compact('titulo', 'autor', 'isbn') + ['preco_venda' => $venda, 'preco_emprestimo' => $emprestimo]);
            foreach ($estados as $i => $estado) {
                Exemplar::create([
                    'livro_id' => $livro->id,
                    'codigo_barras' => sprintf('789%03d%06d', $n + 1, $i + 1),
                    'estado' => $estado,
                ]);
            }
            $livros[$titulo] = $livro;
        }

        $hoje = Carbon::today();
        $emprestar = function (string $cliente, string $titulo, int $retiradaHaDias, int $prazoDias, ?int $devolvidoHaDias = null) use ($clientes, $livros, $admin, $funcionario) {
            $livro = $livros[$titulo];
            $exemplar = $livro->exemplares()->where('situacao', SituacaoExemplar::Disponivel)->first();
            $retirada = Carbon::today()->subDays($retiradaHaDias);

            Emprestimo::create([
                'cliente_id' => $clientes[$cliente]->id,
                'funcionario_id' => ($retiradaHaDias % 2 ? $admin : $funcionario)->id,
                'exemplar_id' => $exemplar->id,
                'data_emprestimo' => $retirada,
                'data_devolucao_prevista' => $retirada->copy()->addDays($prazoDias),
                'data_devolucao' => $devolvidoHaDias !== null ? Carbon::today()->subDays($devolvidoHaDias) : null,
                'valor_cobrado' => $livro->preco_emprestimo,
            ]);

            if ($devolvidoHaDias === null) {
                $exemplar->update(['situacao' => SituacaoExemplar::Emprestado]);
            }
        };

        // Ativos
        $emprestar('Ana Souza', 'O Nome da Rosa', 2, 14);
        $emprestar('Carlos Lima', 'Memórias Póstumas de Brás Cubas', 1, 14);
        $emprestar('Pedro Rocha', 'Capitães da Areia', 0, 14);
        // Atrasados (como na tela de relatórios)
        $emprestar('Marina Alves', 'Quincas Borba', 17, 14);         // 3 dias
        $emprestar('Bruno Dias', 'A Hora da Estrela', 20, 14);       // 6 dias
        $emprestar('Lívia Matos', 'O Nome da Rosa', 16, 14);         // 2 dias
        $emprestar('Tiago Neri', 'Grande Sertão: Veredas', 15, 14);  // 1 dia
        // Devolvidos (histórico p/ "livros mais emprestados")
        foreach ([[30, 16], [40, 28], [50, 38], [60, 47], [70, 60]] as $k => [$ret, $dev]) {
            $emprestar('Rafael Costa', 'Dom Casmurro', $ret, 14, $dev);
            $emprestar('Julia Ramos', 'Vidas Secas', $ret + 1, 14, $dev);
            if ($k < 3) {
                $emprestar('Ana Souza', 'O Cortiço', $ret + 2, 14, $dev);
            }
        }

        // ---------- Vendas ----------
        $vender = function (string $cliente, string $forma, array $itens, float $desconto = 0, int $haDias = 0) use ($clientes, $livros, $formas, $funcionario, $hoje) {
            $subtotal = 0;
            $selecionados = [];
            foreach ($itens as $titulo => $qtd) {
                $livro = $livros[$titulo];
                foreach ($livro->exemplares()->where('situacao', SituacaoExemplar::Disponivel)->limit($qtd)->get() as $ex) {
                    $selecionados[] = [$ex, $livro->preco_venda];
                    $subtotal += (float) $livro->preco_venda;
                }
            }

            $venda = Venda::create([
                'cliente_id' => $clientes[$cliente]->id,
                'funcionario_id' => $funcionario->id,
                'forma_pagamento_id' => $formas[$forma]->id,
                'data_venda' => $hoje->copy()->subDays($haDias)->setTime(10, 30),
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'total' => $subtotal - $desconto,
            ]);

            foreach ($selecionados as [$ex, $preco]) {
                ItemVenda::create(['venda_id' => $venda->id, 'exemplar_id' => $ex->id, 'preco_unitario' => $preco]);
                $ex->update(['situacao' => SituacaoExemplar::Vendido]);
            }
        };

        $vender('Ana Souza', 'cartao_credito', ['O Nome da Rosa' => 1, 'Dom Casmurro' => 2, 'Capitães da Areia' => 1], 5.00, 1);
        $vender('Carlos Lima', 'pix', ['Vidas Secas' => 1], 0, 3);
        $vender('Pedro Rocha', 'cartao_debito', ['Grande Sertão: Veredas' => 1], 0, 5);
        $vender('Julia Ramos', 'dinheiro', ['Dom Casmurro' => 1], 0, 8);
    }
}
