<?php

namespace App\Http\Resources;

use App\Models\Exemplar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Exemplar */
class ExemplarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'livro_id' => $this->livro_id,
            'codigo_barras' => $this->codigo_barras,
            'estado' => $this->estado?->value,
            'estado_rotulo' => $this->estado?->rotulo(),
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'livro' => $this->whenLoaded('livro', fn () => [
                'id' => $this->livro->id,
                'titulo' => $this->livro->titulo,
                'autor' => $this->livro->autor,
                'isbn' => $this->livro->isbn,
                'preco_venda' => (float) $this->livro->preco_venda,
                'preco_emprestimo' => (float) $this->livro->preco_emprestimo,
            ]),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
