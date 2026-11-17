<?php

namespace App\Http\Requests\Livro;

use App\Enums\EstadoExemplar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cadastro do título. Não existe "quantidade": cada exemplar físico é cadastrado
 * individualmente com seu código de barras. Opcionalmente o primeiro exemplar
 * pode ser enviado junto, em "exemplar".
 */
class StoreLivroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'autor' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:20', 'unique:livros,isbn'],
            'preco_venda' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'preco_emprestimo' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'exemplar' => ['nullable', 'array'],
            'exemplar.codigo_barras' => ['required_with:exemplar', 'string', 'max:50', 'unique:exemplares,codigo_barras'],
            'exemplar.estado' => ['nullable', Rule::enum(EstadoExemplar::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.unique' => 'Já existe um livro com este ISBN. Para adicionar mais uma unidade, cadastre um novo exemplar nele.',
            'exemplar.codigo_barras.unique' => 'Este código de barras já pertence a outro exemplar.',
        ];
    }
}
