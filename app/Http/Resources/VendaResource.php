<?php

namespace App\Http\Resources;

use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venda */
class VendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data_venda' => $this->data_venda->toIso8601String(),
            'cliente' => $this->whenLoaded('cliente', fn () => ['id' => $this->cliente->id, 'nome' => $this->cliente->nome, 'email' => $this->cliente->email]),
            'registrado_por' => $this->whenLoaded('funcionario', fn () => ['id' => $this->funcionario->id, 'nome' => $this->funcionario->nome]),
            'forma_pagamento' => new FormaPagamentoResource($this->whenLoaded('formaPagamento')),
            'subtotal' => (float) $this->subtotal,
            'desconto' => (float) $this->desconto,
            'total' => (float) $this->total,
            'quantidade_itens' => $this->itens_count ?? $this->whenLoaded('itens', fn () => $this->itens->count()),
            // Itens agrupados por título (como na tela: preço unitário, qtd., total)
            'itens' => $this->whenLoaded('itens', fn () => $this->itens
                ->groupBy(fn ($i) => $i->exemplar->livro_id)
                ->map(function ($grupo) {
                    $livro = $grupo->first()->exemplar->livro;
                    $unitario = (float) $grupo->first()->preco_unitario;

                    return [
                        'livro_id' => $livro->id,
                        'titulo' => $livro->titulo,
                        'isbn' => $livro->isbn,
                        'preco_unitario' => $unitario,
                        'quantidade' => $grupo->count(),
                        'total' => round((float) $grupo->sum('preco_unitario'), 2),
                        'exemplares' => $grupo->map(fn ($i) => [
                            'item_id' => $i->id,
                            'exemplar_id' => $i->exemplar_id,
                            'codigo_barras' => $i->exemplar->codigo_barras,
                        ])->values(),
                    ];
                })->values()),
        ];
    }
}
