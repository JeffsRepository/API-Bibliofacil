<?php

namespace App\Enums;

enum EstadoExemplar: string
{
    case Novo = 'novo';
    case Bom = 'bom';
    case Desgastado = 'desgastado';
    case Danificado = 'danificado';

    public function rotulo(): string
    {
        return ucfirst($this->value);
    }

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
