<?php

namespace App\Http\Requests\Usuario;

use App\Enums\TipoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarUsuariosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo' => ['nullable', Rule::enum(TipoUsuario::class)],
            'busca' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
