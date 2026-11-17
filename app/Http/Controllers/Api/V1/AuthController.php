<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AlterarSenhaRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** POST /auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('dispositivo') ?? ($request->userAgent() ?: 'api'),
        );

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token' => $resultado['token'],
            'token_type' => 'Bearer',
            'usuario' => new UsuarioResource($resultado['usuario']),
        ]);
    }

    /** GET /auth/me — quem está logado (cabeçalho de todas as telas). */
    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user());
    }

    /** POST /auth/logout  (?todos=1 encerra todas as sessões) */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user(), $request->boolean('todos'));

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /** PUT /auth/senha */
    public function alterarSenha(AlterarSenhaRequest $request): JsonResponse
    {
        $this->auth->alterarSenha($request->user(), $request->validated('senha_atual'), $request->validated('password'));

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }
}
