<?php

namespace App\Services;

use App\Exceptions\RegraNegocioException;
use App\Models\Exemplar;
use App\Models\Livro;
use App\Repositories\ExemplarRepository;

class ExemplarService
{
    public function __construct(private readonly ExemplarRepository $exemplares) {}

    /** Adiciona UM exemplar (unidade física com código de barras único) a um título. */
    public function adicionar(Livro $livro, array $dados): Exemplar
    {
        return $this->exemplares->criar([
            'livro_id' => $livro->id,
            'codigo_barras' => $dados['codigo_barras'],
            'estado' => $dados['estado'] ?? 'bom',
        ])->load('livro');

        /**
         * Alternativa para o load('livro') seria fazer a relação com setRelation('livro', $livro)
         * pois se faço load('livro') o banco de dados faz outra consulta ao banco para trazer um livro
         * que já tenho em mãos.
         * Então poderia ficar assim:
         *
         * return $exemplar->setRalation('livro', $livro);
         */
    }

    /** Apenas estado de conservação e código de barras são editáveis; a situação é controlada pelos fluxos. */
    public function atualizar(Exemplar $exemplar, array $dados): Exemplar
    {
        return $this->exemplares->atualizar($exemplar, $dados)->load('livro');
    }

    public function excluir(Exemplar $exemplar): void
    {
        if ($exemplar->possuiMovimentacao()) {
            throw new RegraNegocioException('Este exemplar possui histórico de empréstimo ou venda e não pode ser excluído.');
        }

        $this->exemplares->excluir($exemplar);
    }

    public function porCodigoBarras(string $codigo): Exemplar
    {
        return $this->exemplares->porCodigoBarras($codigo)
            ?? throw new RegraNegocioException('Nenhum exemplar encontrado com este código de barras.', [], 404);
    }
}