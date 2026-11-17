<?php

namespace App\Http\Requests\Emprestimo;

use Illuminate\Foundation\Http\FormRequest;

/** Renovação de prazo / observações de um empréstimo em aberto. */
class UpdateEmprestimoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'data_devolucao_prevista' => ['sometimes', 'required', 'date', 'after:today'],
            'observacoes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
