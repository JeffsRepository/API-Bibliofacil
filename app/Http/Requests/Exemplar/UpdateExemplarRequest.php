<?php

namespace App\Http\Requests\Exemplar;

use App\Enums\EstadoExemplar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A situação (disponível/emprestado/vendido) não é editável: muda só pelos fluxos de empréstimo e venda. */
class UpdateExemplarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'codigo_barras' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('exemplares', 'codigo_barras')->ignore($this->route('exemplar'))],
            'estado' => ['sometimes', 'required', Rule::enum(EstadoExemplar::class)],
        ];
    }
}
