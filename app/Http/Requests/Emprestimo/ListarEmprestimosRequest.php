<?php

namespace App\Http\Requests\Emprestimo;

use Illuminate\Foundation\Http\FormRequest;

class ListarEmprestimosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:todos,ativos,atrasados,devolvidos,em_aberto'],
            'busca' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
