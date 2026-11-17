<?php

namespace App\Services;

use App\Repositories\LivroRepository;
use App\Repositories\UsuarioRepository;

/** Campo de busca global (cabeçalho do dashboard): livros e clientes. */
class BuscaService
{
    public function __construct(
        private readonly LivroRepository $livros,
        private readonly UsuarioRepository $usuarios,
    ) {}

    public function buscar(string $termo, int $limite = 5): array
    {
        return [
            'livros' => $this->livros->buscarRapido($termo, $limite),
            'clientes' => $this->usuarios->buscarClientes($termo, $limite),
        ];
    }
}
