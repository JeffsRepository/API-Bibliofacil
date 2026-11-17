<?php

namespace App\Http\Requests\Venda;

use Illuminate\Foundation\Http\FormRequest;

/** Itens do carrinho: [{livro_id, quantidade}] + desconto. Usado para calcular o resumo. */
class PreviaVendaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.livro_id' => ['required', 'integer', 'distinct', 'exists:livros,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:100'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return ['itens.*.livro_id.distinct' => 'O mesmo livro foi adicionado mais de uma vez; ajuste a quantidade.'];
    }
}
