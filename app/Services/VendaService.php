<?php

namespace App\Services;

use App\Enums\SituacaoExemplar;
use App\Enums\TipoUsuario;
use App\Exceptions\RegraNegocioException;
use App\Models\FormaPagamento;
use App\Models\Livro;
use App\Models\Usuario;
use App\Models\Venda;
use App\Repositories\ExemplarRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\VendaRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VendaService
{
    public function __construct(
        private readonly VendaRepository $vendas,
        private readonly ExemplarRepository $exemplares,
        private readonly UsuarioRepository $usuarios,
    ) {}

    /**
     * Calcula o resumo do carrinho (itens, subtotal, desconto, total) sem gravar.
     * O carrinho fica no front; esta rota alimenta o painel "Resumo e Pagamento".
     */
    public function calcularResumo(array $itens, float $desconto = 0): array
    {
        $agrupados = $this->agruparItens($itens);
        $livros = Livro::withCount('exemplaresDisponiveis as exemplares_disponiveis_count')
            ->whereIn('id', array_keys($agrupados))
            ->get()
            ->keyBy('id');

        $linhas = [];
        $subtotalCentavos = 0;

        foreach ($agrupados as $livroId => $quantidade) {
            $livro = $livros[$livroId];
            $unitario = $this->centavos($livro->preco_venda);
            $subtotalCentavos += $unitario * $quantidade;

            $linhas[] = [
                'livro_id' => $livro->id,
                'titulo' => $livro->titulo,
                'isbn' => $livro->isbn,
                'preco_unitario' => $unitario / 100,
                'quantidade' => $quantidade,
                'total' => ($unitario * $quantidade) / 100,
                'disponiveis' => (int) $livro->exemplares_disponiveis_count,
                'estoque_suficiente' => $livro->exemplares_disponiveis_count >= $quantidade,
            ];
        }

        $descontoCentavos = $this->centavos($desconto);
        if ($descontoCentavos > $subtotalCentavos) {
            throw new RegraNegocioException('O desconto não pode ser maior que o subtotal.', [
                'desconto' => ['Desconto máximo: R$ '.number_format($subtotalCentavos / 100, 2, ',', '.')],
            ]);
        }

        return [
            'itens' => $linhas,
            'quantidade_itens' => array_sum($agrupados),
            'subtotal' => $subtotalCentavos / 100,
            'desconto' => $descontoCentavos / 100,
            'total' => ($subtotalCentavos - $descontoCentavos) / 100,
        ];
    }

    /**
     * Finaliza a venda: para cada título, trava N exemplares disponíveis,
     * grava uma linha em itens_venda por exemplar e marca-os como vendidos.
     */
    public function finalizar(array $dados, Usuario $responsavel): Venda
    {
        $venda = DB::transaction(function () use ($dados, $responsavel) {
            $cliente = $this->usuarios->encontrar($dados['cliente_id']);
            if ($cliente->tipo !== TipoUsuario::Cliente) {
                throw new RegraNegocioException('O comprador deve ser um cliente.', ['cliente_id' => ['Selecione um usuário do tipo cliente.']]);
            }

            $forma = FormaPagamento::findOrFail($dados['forma_pagamento_id']);
            if (! $forma->ativo) {
                throw new RegraNegocioException('Forma de pagamento inativa.', ['forma_pagamento_id' => ['Selecione uma forma de pagamento ativa.']]);
            }

            $resumo = $this->calcularResumo($dados['itens'], (float) ($dados['desconto'] ?? 0));

            $itensVenda = [];
            $exemplaresVendidos = [];
            foreach ($resumo['itens'] as $linha) {
                $exemplares = $this->exemplares->bloquearDisponiveis($linha['livro_id'], $linha['quantidade']);

                if ($exemplares->count() < $linha['quantidade']) {
                    throw new RegraNegocioException('Estoque insuficiente.', [
                        'itens' => ["\"{$linha['titulo']}\": solicitado {$linha['quantidade']}, disponível {$exemplares->count()}."],
                    ]);
                }

                foreach ($exemplares as $exemplar) {
                    $itensVenda[] = ['exemplar_id' => $exemplar->id, 'preco_unitario' => $linha['preco_unitario']];
                    $exemplaresVendidos[] = $exemplar;
                }
            }

            $venda = $this->vendas->criar([
                'cliente_id' => $cliente->id,
                'funcionario_id' => $responsavel->id,
                'forma_pagamento_id' => $forma->id,
                'data_venda' => Carbon::now(),
                'subtotal' => $resumo['subtotal'],
                'desconto' => $resumo['desconto'],
                'total' => $resumo['total'],
            ]);

            $this->vendas->adicionarItens($venda, $itensVenda);

            foreach ($exemplaresVendidos as $exemplar) {
                $this->exemplares->alterarSituacao($exemplar, SituacaoExemplar::Vendido);
            }

            return $venda;
        });

        return $this->vendas->encontrar($venda->id);
    }

    /** Soma quantidades do mesmo título adicionado mais de uma vez. @return array<int,int> */
    private function agruparItens(array $itens): array
    {
        $agrupados = [];
        foreach ($itens as $item) {
            $id = (int) $item['livro_id'];
            $agrupados[$id] = ($agrupados[$id] ?? 0) + (int) $item['quantidade'];
        }

        return $agrupados;
    }

    /** Trabalha em centavos para evitar erros de ponto flutuante. */
    private function centavos(float|string|null $valor): int
    {
        return (int) round(((float) $valor) * 100);
    }
}
