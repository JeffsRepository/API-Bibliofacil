<?php

namespace App\Http\Requests\Venda;

use App\Enums\TipoUsuario;
use Illuminate\Validation\Rule;

class StoreVendaRequest extends PreviaVendaRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'cliente_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('tipo', TipoUsuario::Cliente->value)],
            'forma_pagamento_id' => ['required', 'integer', Rule::exists('formas_pagamento', 'id')->where('ativo', true)],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'cliente_id.exists' => 'Selecione um cliente cadastrado.',
            'forma_pagamento_id.exists' => 'Selecione uma forma de pagamento cadastrada e ativa.',
        ];
    }
}
