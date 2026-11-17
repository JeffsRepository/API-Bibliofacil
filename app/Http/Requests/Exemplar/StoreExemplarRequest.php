<?php

namespace App\Http\Requests\Exemplar;

use App\Enums\EstadoExemplar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExemplarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'codigo_barras' => ['required', 'string', 'max:50', 'unique:exemplares,codigo_barras'],
            'estado' => ['nullable', Rule::enum(EstadoExemplar::class)],
        ];
    }
}
