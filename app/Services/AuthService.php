<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly UsuarioRepository $usuarios) {}

    /**
     * Autentica funcionário/admin e emite um token Sanctum.
     * Clientes não possuem senha e não acessam a API.
     *
     * @return array{usuario: Usuario, token: string}
     */
    public function login(string $email, string $senha, string $dispositivo = 'api'): array
    {
        $usuario = $this->usuarios->porEmail($email);

        if (! $usuario || ! $usuario->tipo->ehEquipe() || ! $usuario->password || ! Hash::check($senha, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = $usuario->createToken($dispositivo, [$usuario->tipo->value])->plainTextToken;

        return compact('usuario', 'token');
    }

    public function logout(Usuario $usuario, bool $todosDispositivos = false): void
    {
        $todosDispositivos
            ? $usuario->tokens()->delete()
            : $usuario->currentAccessToken()?->delete();
    }

    public function alterarSenha(Usuario $usuario, string $senhaAtual, string $novaSenha): void
    {
        if (! Hash::check($senhaAtual, $usuario->password)) {
            throw ValidationException::withMessages(['senha_atual' => ['A senha atual está incorreta.']]);
        }

        $usuario->update(['password' => $novaSenha]);
    }
}
