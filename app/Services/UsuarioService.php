<?php

namespace App\Services;

use App\Enums\TipoUsuario;
use App\Exceptions\RegraNegocioException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

class UsuarioService
{
    /** Campos que só fazem sentido para cada perfil (os demais ficam nulos). */
    private const CAMPOS_CLIENTE = ['cpf', 'endereco'];

    private const CAMPOS_EQUIPE = ['matricula', 'cargo', 'password'];

    public function __construct(private readonly UsuarioRepository $usuarios) {}

    public function cadastrar(array $dados): Usuario
    {
        return $this->usuarios->criar($this->normalizar($dados, TipoUsuario::from($dados['tipo'])));
    }

    public function atualizar(Usuario $usuario, array $dados, Usuario $ator): Usuario
    {
        $novoTipo = isset($dados['tipo']) ? TipoUsuario::from($dados['tipo']) : $usuario->tipo;

        if ($usuario->is($ator) && $novoTipo !== $usuario->tipo) {
            throw new RegraNegocioException('Você não pode alterar o seu próprio tipo de usuário.');
        }

        if ($usuario->ehAdmin() && $novoTipo !== TipoUsuario::Admin && $this->usuarios->contarAdmins() <= 1) {
            throw new RegraNegocioException('O sistema precisa de pelo menos um administrador.');
        }

        if ($novoTipo->ehEquipe() && ! $usuario->password && empty($dados['password'])) {
            throw new RegraNegocioException('Informe uma senha para o funcionário/admin.', ['password' => ['A senha é obrigatória para funcionários e admins.']]);
        }

        if (array_key_exists('password', $dados) && empty($dados['password'])) {
            unset($dados['password']); // senha em branco na edição = manter a atual
        }

        return $this->usuarios->atualizar($usuario, $this->normalizar($dados, $novoTipo, parcial: true));
    }

    public function excluir(Usuario $usuario, Usuario $ator): void
    {
        if ($usuario->is($ator)) {
            throw new RegraNegocioException('Você não pode excluir a sua própria conta.');
        }

        if ($usuario->ehAdmin() && $this->usuarios->contarAdmins() <= 1) {
            throw new RegraNegocioException('O sistema precisa de pelo menos um administrador.');
        }

        if ($this->usuarios->possuiMovimentacao($usuario)) {
            throw new RegraNegocioException('Este usuário possui empréstimos ou vendas vinculados e não pode ser excluído.');
        }

        $this->usuarios->excluir($usuario);
    }

    /**
     * Garante a consistência por perfil: cliente não tem senha/matrícula/cargo;
     * funcionário/admin não tem CPF/endereço. Na edição sem troca de tipo,
     * campos de outro perfil enviados são simplesmente ignorados.
     */
    private function normalizar(array $dados, TipoUsuario $tipo, bool $parcial = false): array
    {
        $alheios = $tipo === TipoUsuario::Cliente ? self::CAMPOS_EQUIPE : self::CAMPOS_CLIENTE;
        $trocouOuDefiniuTipo = ! $parcial || isset($dados['tipo']);

        foreach ($alheios as $campo) {
            if ($trocouOuDefiniuTipo) {
                $dados[$campo] = null;
            } else {
                unset($dados[$campo]);
            }
        }

        return $dados;
    }
}
