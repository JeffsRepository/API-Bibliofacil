<?php

namespace App\Http\Requests\Emprestimo;

use App\Enums\EstadoExemplar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DevolucaoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'data_devolucao' => ['nullable', 'date', 'before_or_equal:today'],
            'estado' => ['nullable', Rule::enum(EstadoExemplar::class)], // conservação conferida na devolução
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
