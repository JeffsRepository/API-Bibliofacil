<?php

namespace App\Http\Requests\Livro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLivroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'autor' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('livros', 'isbn')->ignore($this->route('livro'))],
            'preco_venda' => ['sometimes', 'required', 'numeric', 'min:0'],
            'preco_emprestimo' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
