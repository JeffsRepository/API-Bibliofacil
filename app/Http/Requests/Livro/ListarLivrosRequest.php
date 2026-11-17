<?php

namespace App\Http\Requests\Livro;

use Illuminate\Foundation\Http\FormRequest;

class ListarLivrosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'busca' => ['nullable', 'string', 'max:100'],
            'situacao' => ['nullable', 'in:todos,disponiveis,indisponiveis'],
            'ordenar' => ['nullable', 'in:titulo,autor,isbn,created_at'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
