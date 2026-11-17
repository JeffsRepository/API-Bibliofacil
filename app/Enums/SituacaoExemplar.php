<?php

namespace App\Enums;

enum SituacaoExemplar: string
{
    case Disponivel = 'disponivel';
    case Emprestado = 'emprestado';
    case Vendido = 'vendido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Emprestado => 'Emprestado',
            self::Vendido => 'Vendido',
        };
    }

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
