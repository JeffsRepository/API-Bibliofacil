<?php

namespace App\Services;

use App\Repositories\EmprestimoRepository;
use App\Repositories\LivroRepository;
use App\Repositories\VendaRepository;
use App\Support\Periodo;
use Illuminate\Support\Collection;

class RelatorioService
{
    public function __construct(
        private readonly EmprestimoRepository $emprestimos,
        private readonly VendaRepository $vendas,
        private readonly LivroRepository $livros,
    ) {}

    /** Cards do topo: realizados, devolvidos, em atraso (hoje) e vendas no período. */
    public function resumo(Periodo $periodo): array
    {
        $vendas = $this->vendas->totaisNoPeriodo($periodo);

        return [
            'periodo' => $periodo->toArray(),
            'emprestimos_realizados' => $this->emprestimos->contarRealizadosNoPeriodo($periodo),
            'emprestimos_devolvidos' => $this->emprestimos->contarDevolvidosNoPeriodo($periodo),
            'emprestimos_em_atraso' => $this->emprestimos->contarAtrasados(), // sempre "hoje"
            'receita_emprestimos' => round($this->emprestimos->receitaNoPeriodo($periodo), 2),
            'vendas_realizadas' => $vendas['quantidade'],
            'vendas_valor_total' => $vendas['total'],
        ];
    }

    public function livrosMaisEmprestados(Periodo $periodo, int $limite = 10): Collection
    {
        return $this->livros->maisEmprestados($periodo, $limite)->map(fn ($l) => [
            'livro_id' => $l->id,
            'titulo' => $l->titulo,
            'autor' => $l->autor,
            'isbn' => $l->isbn,
            'emprestimos' => (int) $l->total_emprestimos,
        ]);
    }

    /** Vendas por forma de pagamento + linha de total geral. */
    public function vendasPorFormaPagamento(Periodo $periodo): array
    {
        $linhas = $this->vendas->porFormaPagamento($periodo)->map(fn ($f) => [
            'forma_pagamento_id' => $f->id,
            'metodo' => $f->nome,
            'slug' => $f->slug,
            'vendas' => (int) $f->quantidade_vendas,
            'total' => round((float) $f->valor_total, 2),
        ]);

        return [
            'periodo' => $periodo->toArray(),
            'formas' => $linhas->values(),
            'total_geral' => [
                'vendas' => $linhas->sum('vendas'),
                'total' => round($linhas->sum('total'), 2),
            ],
        ];
    }

    public function vendasPorMes(Periodo $periodo): Collection
    {
        return $this->vendas->porMes($periodo)->map(fn ($m) => [
            'mes' => $m->mes,
            'vendas' => (int) $m->quantidade_vendas,
            'total' => round((float) $m->valor_total, 2),
        ]);
    }

    public function emprestimosEmAtraso(int $limite = 50): Collection
    {
        return $this->emprestimos->atrasados($limite)->map(fn ($e) => [
            'emprestimo_id' => $e->id,
            'leitor' => $e->cliente->nome,
            'cliente_id' => $e->cliente_id,
            'telefone' => $e->cliente->telefone,
            'livro' => $e->exemplar->livro->titulo,
            'codigo_barras' => $e->exemplar->codigo_barras,
            'previsto' => $e->data_devolucao_prevista->toDateString(),
            'dias_atraso' => $e->diasAtraso(),
            'atraso' => $e->diasAtraso().' '.($e->diasAtraso() === 1 ? 'dia' : 'dias'),
        ]);
    }

    /** Tela completa de relatórios em uma única chamada. */
    public function completo(Periodo $periodo, int $limite = 10): array
    {
        return [
            'resumo' => $this->resumo($periodo),
            'livros_mais_emprestados' => $this->livrosMaisEmprestados($periodo, $limite),
            'vendas_por_forma_pagamento' => $this->vendasPorFormaPagamento($periodo),
            'emprestimos_em_atraso' => $this->emprestimosEmAtraso(),
        ];
    }
}
