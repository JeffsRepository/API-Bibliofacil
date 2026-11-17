<?php

namespace App\Repositories;

use App\Models\Emprestimo;
use App\Support\Periodo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmprestimoRepository
{
    private const RELACOES = ['cliente:id,nome,email,telefone', 'funcionario:id,nome', 'exemplar.livro'];

    /**
     * Filtros: status (todos|ativos|atrasados|devolvidos|em_aberto), busca (leitor/livro),
     * cliente_id, de/ate (data do empréstimo).
     */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        return $this->queryFiltrada($filtros)
            ->with(self::RELACOES)
            ->orderByDesc('data_emprestimo')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /** Contadores das abas "Todos / Ativos / Atrasados / Devolvidos". */
    public function contadores(array $filtros = []): array
    {
        $base = fn () => $this->queryFiltrada(array_merge($filtros, ['status' => null]));

        return [
            'todos' => $base()->count(),
            'ativos' => $base()->ativos()->count(),
            'atrasados' => $base()->atrasados()->count(),
            'devolvidos' => $base()->devolvidos()->count(),
        ];
    }

    public function encontrar(int $id): Emprestimo
    {
        return Emprestimo::with(self::RELACOES)->findOrFail($id);
    }

    public function encontrarParaAtualizar(int $id): Emprestimo
    {
        return Emprestimo::lockForUpdate()->findOrFail($id);
    }

    public function criar(array $dados): Emprestimo
    {
        return Emprestimo::create($dados);
    }

    public function atualizar(Emprestimo $emprestimo, array $dados): Emprestimo
    {
        $emprestimo->update($dados);

        return $emprestimo;
    }

    /** Empréstimos em aberto (ativos + atrasados) de um cliente. */
    public function emAbertoDoCliente(int $clienteId): Collection
    {
        return Emprestimo::emAberto()
            ->where('cliente_id', $clienteId)
            ->with('exemplar.livro')
            ->orderBy('data_devolucao_prevista')
            ->get();
    }

    public function atrasados(int $limite = 50): Collection
    {
        return Emprestimo::atrasados()
            ->with(self::RELACOES)
            ->orderBy('data_devolucao_prevista')
            ->limit($limite)
            ->get();
    }

    public function contarAtivos(): int
    {
        return Emprestimo::ativos()->count();
    }

    public function contarAtrasados(): int
    {
        return Emprestimo::atrasados()->count();
    }

    public function contarRealizadosNoPeriodo(Periodo $periodo): int
    {
        return Emprestimo::query()
            ->when($periodo->inicio, fn ($q, $d) => $q->whereDate('data_emprestimo', '>=', $d))
            ->when($periodo->fim, fn ($q, $d) => $q->whereDate('data_emprestimo', '<=', $d))
            ->count();
    }

    public function contarDevolvidosNoPeriodo(Periodo $periodo): int
    {
        return Emprestimo::devolvidos()
            ->when($periodo->inicio, fn ($q, $d) => $q->whereDate('data_devolucao', '>=', $d))
            ->when($periodo->fim, fn ($q, $d) => $q->whereDate('data_devolucao', '<=', $d))
            ->count();
    }

    public function receitaNoPeriodo(Periodo $periodo): float
    {
        return (float) Emprestimo::query()
            ->when($periodo->inicio, fn ($q, $d) => $q->whereDate('data_emprestimo', '>=', $d))
            ->when($periodo->fim, fn ($q, $d) => $q->whereDate('data_emprestimo', '<=', $d))
            ->sum('valor_cobrado');
    }

    private function queryFiltrada(array $filtros): Builder
    {
        $q = Emprestimo::query();

        match ($filtros['status'] ?? null) {
            'ativos' => $q->ativos(),
            'atrasados' => $q->atrasados(),
            'devolvidos' => $q->devolvidos(),
            'em_aberto' => $q->emAberto(),
            default => null,
        };

        // O when otimiza pois não precisa de if else.
        return $q
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($filtros['de'] ?? null, fn ($q, $d) => $q->whereDate('data_emprestimo', '>=', $d))
            ->when($filtros['ate'] ?? null, fn ($q, $d) => $q->whereDate('data_emprestimo', '<=', $d))
            ->when($filtros['busca'] ?? null, fn ($q, $busca) => $q->where(fn ($w) => $w
                ->whereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))
                ->orWhereHas('exemplar', fn ($e) => $e->where('codigo_barras', $busca)
                    ->orWhereHas('livro', fn ($l) => $l->where('titulo', 'like', "%{$busca}%")
                        ->orWhere('isbn', 'like', "%{$busca}%")))));
    }
}