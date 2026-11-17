<?php

namespace App\Repositories;

use App\Enums\SituacaoExemplar;
use App\Models\Exemplar;
use Illuminate\Support\Collection;

class ExemplarRepository
{
    public function listarDoLivro(int $livroId, ?string $situacao = null): Collection
    {
        return Exemplar::where('livro_id', $livroId)
            ->when($situacao, fn ($q, $s) => $q->where('situacao', $s))
            ->orderBy('codigo_barras')
            ->get();
    }

    public function encontrar(int $id): Exemplar
    {
        return Exemplar::with('livro')->findOrFail($id);
    }

    public function porCodigoBarras(string $codigo): ?Exemplar
    {
        return Exemplar::with('livro')->where('codigo_barras', $codigo)->first();
    }

    public function criar(array $dados): Exemplar
    {
        return Exemplar::create($dados);
    }

    public function atualizar(Exemplar $exemplar, array $dados): Exemplar
    {
        $exemplar->update($dados);

        return $exemplar;
    }

    public function excluir(Exemplar $exemplar): void
    {
        $exemplar->delete();
    }

    public function alterarSituacao(Exemplar $exemplar, SituacaoExemplar $situacao): void
    {
        $exemplar->update(['situacao' => $situacao]);
    }

    /**
     * Bloqueia (SELECT ... FOR UPDATE) N exemplares disponíveis de um título.
     * Deve ser chamado dentro de DB::transaction.
     */
    public function bloquearDisponiveis(int $livroId, int $quantidade): Collection
    {
        return Exemplar::where('livro_id', $livroId)
            ->where('situacao', SituacaoExemplar::Disponivel)
            ->orderBy('id')
            ->limit($quantidade)
            ->lockForUpdate()
            ->get();
    }

    /** Bloqueia um exemplar específico (escolhido pelo código de barras). */
    public function bloquearPorCodigo(string $codigo): ?Exemplar
    {
        return Exemplar::with('livro')->where('codigo_barras', $codigo)->lockForUpdate()->first();
    }

    public function totalDisponiveis(): int
    {
        return Exemplar::where('situacao', SituacaoExemplar::Disponivel)->count();
    }
}
