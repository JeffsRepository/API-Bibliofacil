<?php

namespace App\Http\Requests\Emprestimo;

class PreviaEmprestimoRequest extends StoreEmprestimoRequest
{
    protected function regraDataPrevista(): array
    {
        return ['nullable', 'date', 'after:today'];
    }
}
