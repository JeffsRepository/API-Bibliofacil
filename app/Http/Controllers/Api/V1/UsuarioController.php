<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\ListarUsuariosRequest;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $service,
        private readonly UsuarioRepository $repository,
    ) {}

    /** GET /usuarios?tipo=cliente|funcionario|admin&busca=&per_page= */
    public function index(ListarUsuariosRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        return UsuarioResource::collection($this->repository->paginar($filtros, (int) ($filtros['per_page'] ?? 15)))
            ->additional(['meta' => ['contadores' => $this->repository->contadores($filtros['busca'] ?? null)]]);
    }

    /** GET /usuarios/{usuario} */
    public function show(Usuario $usuario): UsuarioResource
    {
        Gate::authorize('view', $usuario);

        return new UsuarioResource($usuario);
    }

    /** POST /usuarios — admin cria qualquer tipo; funcionário só clientes. */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        return (new UsuarioResource($this->service->cadastrar($request->validated())))
            ->additional(['message' => 'Usuário cadastrado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /usuarios/{usuario} */
    public function update(UpdateUsuarioRequest $request, Usuario $usuario): UsuarioResource
    {
        return (new UsuarioResource($this->service->atualizar($usuario, $request->validated(), $request->user())))
            ->additional(['message' => 'Usuário atualizado com sucesso.']);
    }

    /** DELETE /usuarios/{usuario} (admin) */
    public function destroy(Request $request, Usuario $usuario): JsonResponse
    {
        Gate::authorize('delete', $usuario);
        $this->service->excluir($usuario, $request->user());

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }
}
