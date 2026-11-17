<?php

namespace App\Http\Requests\FormaPagamento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarFormaPagamentoRequest extends FormRequest
{
    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'nome' => [$obrigatorio, 'string', 'max:60', Rule::unique('formas_pagamento', 'nome')->ignore($this->route('formaPagamento'))],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
