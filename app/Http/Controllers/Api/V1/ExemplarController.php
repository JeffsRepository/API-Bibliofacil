<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SituacaoExemplar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exemplar\StoreExemplarRequest;
use App\Http\Requests\Exemplar\UpdateExemplarRequest;
use App\Http\Resources\ExemplarResource;
use App\Models\Exemplar;
use App\Models\Livro;
use App\Repositories\ExemplarRepository;
use App\Services\ExemplarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ExemplarController extends Controller
{
    public function __construct(
        private readonly ExemplarService $service,
        private readonly ExemplarRepository $repository,
    ) {}

    /** GET /livros/{livro}/exemplares?situacao=disponivel|emprestado|vendido */
    public function index(Request $request, Livro $livro): AnonymousResourceCollection
    {
        $dados = $request->validate(['situacao' => ['nullable', Rule::enum(SituacaoExemplar::class)]]);

        return ExemplarResource::collection($this->repository->listarDoLivro($livro->id, $dados['situacao'] ?? null));
    }

    /** POST /livros/{livro}/exemplares (admin) — um exemplar por vez. */
    public function store(StoreExemplarRequest $request, Livro $livro): JsonResponse
    {
        return (new ExemplarResource($this->service->adicionar($livro, $request->validated())))
            ->additional(['message' => 'Exemplar cadastrado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /** GET /exemplares/{exemplar} */
    public function show(Exemplar $exemplar): ExemplarResource
    {
        return new ExemplarResource($exemplar->load('livro'));
    }

    /** GET /exemplares/codigo/{codigo} — leitura do código de barras. */
    public function porCodigo(string $codigo): ExemplarResource
    {
        return new ExemplarResource($this->service->porCodigoBarras($codigo));
    }

    /** PUT/PATCH /exemplares/{exemplar} (admin) */
    public function update(UpdateExemplarRequest $request, Exemplar $exemplar): ExemplarResource
    {
        return (new ExemplarResource($this->service->atualizar($exemplar, $request->validated())))
            ->additional(['message' => 'Exemplar atualizado com sucesso.']);
    }

    /** DELETE /exemplares/{exemplar} (admin) */
    public function destroy(Exemplar $exemplar): JsonResponse
    {
        $this->service->excluir($exemplar);

        return response()->json(['message' => 'Exemplar excluído com sucesso.']);
    }
}