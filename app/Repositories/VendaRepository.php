<?php

namespace App\Repositories;

use App\Models\FormaPagamento;
use App\Models\Venda;
use App\Support\Periodo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VendaRepository
{
    private const RELACOES = ['cliente:id,nome,email', 'funcionario:id,nome', 'formaPagamento', 'itens.exemplar.livro'];

    /** Filtros: de, ate, mes, cliente_id, forma_pagamento_id, busca (cliente). */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        $periodo = Periodo::deArray($filtros);

        return Venda::query()
            ->with(self::RELACOES)
            ->withCount('itens')
            ->tap(fn (Builder $q) => $this->aplicarPeriodo($q, $periodo))
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($filtros['forma_pagamento_id'] ?? null, fn ($q, $id) => $q->where('forma_pagamento_id', $id))
            ->when($filtros['busca'] ?? null, fn ($q, $b) => $q->whereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$b}%")))
            ->orderByDesc('data_venda')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function encontrar(int $id): Venda
    {
        return Venda::with(self::RELACOES)->findOrFail($id);
    }

    public function criar(array $dados): Venda
    {
        return Venda::create($dados);
    }

    public function adicionarItens(Venda $venda, array $itens): void
    {
        $venda->itens()->createMany($itens);
    }

    /** @return array{quantidade:int,total:float} */
    public function totaisNoPeriodo(Periodo $periodo): array
    {
        $linha = Venda::query()
            ->tap(fn (Builder $q) => $this->aplicarPeriodo($q, $periodo))
            ->selectRaw('COUNT(*) as quantidade, COALESCE(SUM(total), 0) as total')
            ->first();

        return ['quantidade' => (int) $linha->quantidade, 'total' => round((float) $linha->total, 2)];
    }

    /**
     * Relatório: vendas por forma de pagamento.
     * Lista todas as formas cadastradas (inclusive com zero vendas no período).
     */
    public function porFormaPagamento(Periodo $periodo): Collection
    {
        // tabela principal formas_pagamentos ela convida vendas a participar da consulta. LeftJoin() garante que tudo de formas_pagamentos vai estar presente.
        return FormaPagamento::query()
            ->select('formas_pagamento.id', 'formas_pagamento.nome', 'formas_pagamento.slug')
            ->selectRaw('COUNT(vendas.id) as quantidade_vendas')
            ->selectRaw('COALESCE(SUM(vendas.total), 0) as valor_total')
            ->leftJoin('vendas', function ($join) use ($periodo) {
                $join->on('vendas.forma_pagamento_id', '=', 'formas_pagamento.id');
                if ($periodo->inicio) {
                    $join->where('vendas.data_venda', '>=', $periodo->inicio->toDateTimeString());
                }
                if ($periodo->fim) {
                    $join->where('vendas.data_venda', '<=', $periodo->fim->toDateTimeString());
                }
            })
            ->groupBy('formas_pagamento.id', 'formas_pagamento.nome', 'formas_pagamento.slug')
            ->orderByDesc('valor_total')
            ->get();
    }

    /** Relatório: vendas agrupadas por mês (YYYY-MM). */
    public function porMes(Periodo $periodo): Collection
    {
        return Venda::query()
            ->tap(fn (Builder $q) => $this->aplicarPeriodo($q, $periodo))
            ->selectRaw("strftime('%Y-%m', data_venda) as mes")
            ->selectRaw('COUNT(*) as quantidade_vendas, SUM(total) as valor_total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    private function aplicarPeriodo(Builder $q, Periodo $periodo): Builder
    {
        return $q
            ->when($periodo->inicio, fn ($q, $d) => $q->where('data_venda', '>=', $d->toDateTimeString()))
            ->when($periodo->fim, fn ($q, $d) => $q->where('data_venda', '<=', $d->toDateTimeString()));
    }
}
