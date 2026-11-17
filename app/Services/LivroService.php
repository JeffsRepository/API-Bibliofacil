<?php

namespace App\Services;

use App\Exceptions\RegraNegocioException;
use App\Models\Livro;
use App\Repositories\ExemplarRepository;
use App\Repositories\LivroRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class LivroService
{
    public function __construct(
        private readonly LivroRepository $livros,
        private readonly ExemplarRepository $exemplares,
    ) {}

    /**
     * Cadastra o título e, opcionalmente, o primeiro exemplar (um por vez,
     * pois cada exemplar tem código de barras único — não existe "quantidade").
     */
    public function cadastrar(array $dados): Livro
    {
        return DB::transaction(function () use ($dados) {
            $livro = $this->livros->criar(Arr::only($dados, ['isbn', 'titulo', 'autor', 'preco_venda', 'preco_emprestimo']));

            if (! empty($dados['exemplar'])) {
                $this->exemplares->criar([
                    'livro_id' => $livro->id,
                    'codigo_barras' => $dados['exemplar']['codigo_barras'],
                    'estado' => $dados['exemplar']['estado'] ?? 'bom',
                ]);
            }

            return $this->livros->encontrar($livro->id);
        });
    }

    public function atualizar(Livro $livro, array $dados): Livro
    {
        $this->livros->atualizar($livro, $dados);

        return $this->livros->encontrar($livro->id);
    }

    /** Só exclui títulos sem histórico (empréstimos/vendas), preservando os registros. */
    public function excluir(Livro $livro): void
    {
        if ($this->livros->possuiMovimentacao($livro)) {
            throw new RegraNegocioException('Este livro possui empréstimos ou vendas registrados e não pode ser excluído.');
        }

        DB::transaction(function () use ($livro) {
            $livro->exemplares()->delete();
            $this->livros->excluir($livro);
        });
    }
}
