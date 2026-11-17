<?php

namespace App\Policies;

use App\Enums\TipoUsuario;
use App\Models\Usuario;

/**
 * Regras de quem pode gerenciar quem:
 * - Admin: cria/edita/exclui qualquer tipo (inclusive outros admins).
 * - Funcionário: cria e edita apenas clientes.
 */
class UsuarioPolicy
{
    public function viewAny(Usuario $ator): bool
    {
        return $ator->tipo->ehEquipe();
    }

    public function view(Usuario $ator, Usuario $alvo): bool
    {
        return $ator->ehAdmin() || $alvo->ehCliente() || $ator->is($alvo);
    }

    public function create(Usuario $ator, TipoUsuario $tipo): bool
    {
        return $ator->ehAdmin() || $tipo === TipoUsuario::Cliente;
    }

    public function update(Usuario $ator, Usuario $alvo, ?TipoUsuario $novoTipo = null): bool
    {
        if ($ator->ehAdmin()) {
            return true;
        }

        return $alvo->ehCliente() && ($novoTipo === null || $novoTipo === TipoUsuario::Cliente);
    }

    public function delete(Usuario $ator, Usuario $alvo): bool
    {
        return $ator->ehAdmin();
    }
}
