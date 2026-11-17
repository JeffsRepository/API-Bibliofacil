<?php

namespace App\Enums;

enum TipoUsuario: string
{
    case Cliente = 'cliente';
    case Funcionario = 'funcionario';
    case Admin = 'admin';

    public function rotulo(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Funcionario => 'Funcionário',
            self::Admin => 'Admin',
        };
    }

    /** Funcionários e admins operam o sistema (fazem login). */
    public function ehEquipe(): bool
    {
        return $this !== self::Cliente;
    }

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
