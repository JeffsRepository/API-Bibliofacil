<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Livro\ListarLivrosRequest;
use App\Http\Requests\Livro\StoreLivroRequest;
use App\Http\Requests\Livro\UpdateLivroRequest;
use App\Http\Resources\LivroResource;
use App\Models\Livro;
use App\Repositories\LivroRepository;
use App\Services\LivroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LivroController extends Controller
{
    public function __construct(
        private readonly LivroService $service,
        private readonly LivroRepository $repository,
    ) {}

    /** GET /livros?busca=&situacao=todos|disponiveis|indisponiveis&per_page= */
    public function index(ListarLivrosRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        return LivroResource::collection($this->repository->paginar($filtros, (int) ($filtros['per_page'] ?? 15)))
            ->additional(['meta' => ['contadores' => $this->repository->contadores($filtros['busca'] ?? null)]]);
    }

    /** GET /livros/{livro} — detalhe com todos os exemplares. */
    public function show(Livro $livro): LivroResource
    {
        return new LivroResource($this->repository->encontrar($livro->id));
    }

    /** POST /livros (admin) */
    public function store(StoreLivroRequest $request): JsonResponse
    {
        $livro = $this->service->cadastrar($request->validated());

        return (new LivroResource($livro))
            ->additional(['message' => 'Livro cadastrado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /livros/{livro} (admin) */
    public function update(UpdateLivroRequest $request, Livro $livro): LivroResource
    {
        return (new LivroResource($this->service->atualizar($livro, $request->validated())))
            ->additional(['message' => 'Livro atualizado com sucesso.']);
    }

    /** DELETE /livros/{livro} (admin) */
    public function destroy(Livro $livro): JsonResponse
    {
        $this->service->excluir($livro);

        return response()->json(['message' => 'Livro excluído com sucesso.']);
    }
}
