<?php

namespace App\Repositories;

use App\Enums\SituacaoExemplar;
use App\Models\Livro;
use App\Support\Periodo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LivroRepository
{
    /**
     * Listagem do acervo com contagem de exemplares (estoque calculado).
     * Filtros: busca (título, autor, ISBN), situacao (disponiveis|indisponiveis).
     */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        return $this->queryComEstoque()
            ->when($filtros['busca'] ?? null, fn (Builder $q, $busca) => $this->aplicarBusca($q, $busca))
            ->when(($filtros['situacao'] ?? null) === 'disponiveis', fn (Builder $q) => $q->has('exemplaresDisponiveis'))
            ->when(($filtros['situacao'] ?? null) === 'indisponiveis', fn (Builder $q) => $q->doesntHave('exemplaresDisponiveis'))
            ->orderBy($filtros['ordenar'] ?? 'titulo')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /** Contadores das abas "Todos / Disponíveis / Indisponíveis". */
    public function contadores(?string $busca = null): array
    {
        $base = fn () => Livro::query()->when($busca, fn (Builder $q) => $this->aplicarBusca($q, $busca));

        $todos = $base()->count();
        $disponiveis = $base()->has('exemplaresDisponiveis')->count();

        return [
            'todos' => $todos,
            'disponiveis' => $disponiveis,
            'indisponiveis' => $todos - $disponiveis,
        ];
    }

    public function encontrar(int $id): Livro
    {
        return $this->queryComEstoque()->with(['exemplares' => fn ($q) => $q->orderBy('codigo_barras')])->findOrFail($id);
    }

    public function buscarRapido(string $termo, int $limite = 10): Collection
    {
        return $this->queryComEstoque()
            ->tap(fn (Builder $q) => $this->aplicarBusca($q, $termo))
            ->orderBy('titulo')
            ->limit($limite)
            ->get();
    }

    public function criar(array $dados): Livro
    {
        return Livro::create($dados);
    }

    public function atualizar(Livro $livro, array $dados): Livro
    {
        $livro->update($dados);

        return $livro;
    }

    public function excluir(Livro $livro): void
    {
        $livro->delete();
    }

    public function possuiMovimentacao(Livro $livro): bool
    {
        return $livro->exemplares()
            ->where(fn (Builder $q) => $q->has('emprestimos')->orHas('itemVenda'))
            ->exists();
    }

    /** Relatório: livros mais emprestados no período (agrupa por livro via exemplares). */
    public function maisEmprestados(Periodo $periodo, int $limite = 10): Collection
    {
        return Livro::query()
            ->select('livros.id', 'livros.titulo', 'livros.autor', 'livros.isbn')
            ->selectRaw('COUNT(emprestimos.id) as total_emprestimos')
            ->join('exemplares', 'exemplares.livro_id', '=', 'livros.id')
            ->join('emprestimos', 'emprestimos.exemplar_id', '=', 'exemplares.id')
            ->when($periodo->inicio, fn ($q, $d) => $q->whereDate('emprestimos.data_emprestimo', '>=', $d))
            ->when($periodo->fim, fn ($q, $d) => $q->whereDate('emprestimos.data_emprestimo', '<=', $d))
            ->groupBy('livros.id', 'livros.titulo', 'livros.autor', 'livros.isbn')
            ->orderByDesc('total_emprestimos')
            ->orderBy('livros.titulo')
            ->limit($limite)
            ->get();
    }

    private function queryComEstoque(): Builder
    {
        return Livro::query()
            ->withCount([
                'exemplares as total_exemplares_count' => fn ($q) => $q->where('situacao', '!=', SituacaoExemplar::Vendido),
                'exemplaresDisponiveis as exemplares_disponiveis_count',
                'exemplares as exemplares_emprestados_count' => fn ($q) => $q->where('situacao', SituacaoExemplar::Emprestado),
            ])
            ->with(['exemplares' => fn ($q) => $q->select('id', 'livro_id', 'estado', 'situacao')
                ->where('situacao', '!=', SituacaoExemplar::Vendido)]);
    }

    private function aplicarBusca(Builder $q, string $busca): Builder
    {
        return $q->where(fn (Builder $w) => $w
            ->where('titulo', 'like', "%{$busca}%")
            ->orWhere('autor', 'like', "%{$busca}%")
            ->orWhere('isbn', 'like', "%{$busca}%")
            ->orWhereHas('exemplares', fn ($e) => $e->where('codigo_barras', $busca)));
    }
}
