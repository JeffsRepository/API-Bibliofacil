<?php

namespace App\Enums;

/** Status calculado (não gravado no banco). */
enum StatusEmprestimo: string
{
    case Ativo = 'ativo';
    case Atrasado = 'atrasado';
    case Devolvido = 'devolvido';

    public function rotulo(): string
    {
        return ucfirst($this->value);
    }
}
