<?php

namespace App\Repositories;

use App\Models\FormaPagamento;
use Illuminate\Support\Collection;

class FormaPagamentoRepository
{
    public function listar(bool $somenteAtivas = false): Collection
    {
        return FormaPagamento::query()
            ->when($somenteAtivas, fn ($q) => $q->ativas())
            ->orderBy('nome')
            ->get();
    }

    public function encontrar(int $id): FormaPagamento
    {
        return FormaPagamento::findOrFail($id);
    }

    public function criar(array $dados): FormaPagamento
    {
        return FormaPagamento::create($dados);
    }

    public function atualizar(FormaPagamento $forma, array $dados): FormaPagamento
    {
        $forma->update($dados);

        return $forma;
    }

    public function excluir(FormaPagamento $forma): void
    {
        $forma->delete();
    }

    public function possuiVendas(FormaPagamento $forma): bool
    {
        return $forma->vendas()->exists();
    }
}
