<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LivroResource;
use App\Http\Resources\UsuarioResource;
use App\Services\BuscaService;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly BuscaService $busca,
    ) {}

    /** GET /dashboard — usuário logado, indicadores e atalhos. */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'data' => [
                'usuario_logado' => new UsuarioResource($usuario),
                'saudacao' => 'Bem-vindo ao BIBLIOFÁCIL, '.strtok($usuario->nome, ' ').'!',
                ...$this->dashboard->montar($usuario),
            ],
        ]);
    }

    /** GET /busca?q=termo — campo de busca global. */
    public function buscar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $resultado = $this->busca->buscar($dados['q'], (int) ($dados['limite'] ?? 5));

        return response()->json([
            'data' => [
                'termo' => $dados['q'],
                'livros' => LivroResource::collection($resultado['livros']),
                'clientes' => UsuarioResource::collection($resultado['clientes']),
            ],
        ]);
    }
}
