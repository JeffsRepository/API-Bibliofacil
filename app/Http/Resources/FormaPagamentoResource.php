<?php

namespace App\Http\Resources;

use App\Models\FormaPagamento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormaPagamento */
class FormaPagamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'slug' => $this->slug,
            'ativo' => $this->ativo,
        ];
    }
}
