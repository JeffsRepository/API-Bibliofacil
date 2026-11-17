<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emprestimo\DevolucaoRequest;
use App\Http\Requests\Emprestimo\ListarEmprestimosRequest;
use App\Http\Requests\Emprestimo\PreviaEmprestimoRequest;
use App\Http\Requests\Emprestimo\StoreEmprestimoRequest;
use App\Http\Requests\Emprestimo\UpdateEmprestimoRequest;
use App\Http\Resources\EmprestimoResource;
use App\Http\Resources\ExemplarResource;
use App\Http\Resources\LivroResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Emprestimo;
use App\Repositories\EmprestimoRepository;
use App\Services\EmprestimoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmprestimoController extends Controller
{
    public function __construct(
        private readonly EmprestimoService $service,
        private readonly EmprestimoRepository $repository,
    ) {}

    /** GET /emprestimos?status=todos|ativos|atrasados|devolvidos|em_aberto&busca=&de=&ate= */
    public function index(ListarEmprestimosRequest $request): AnonymousResourceCollection
    {
        return $this->listar($request->validated());
    }

    /** GET /emprestimos/ativos — atalho: em aberto e dentro do prazo. */
    public function ativos(ListarEmprestimosRequest $request): AnonymousResourceCollection
    {
        return $this->listar(['status' => 'ativos'] + $request->validated());
    }

    /** GET /emprestimos/atrasados — atalho: em aberto e com prazo vencido. */
    public function atrasados(ListarEmprestimosRequest $request): AnonymousResourceCollection
    {
        // concatena arrays associativos com +. Pode usar o array_merge().
        return $this->listar(['status' => 'atrasados'] + $request->validated());
    }

    private function listar(array $filtros): AnonymousResourceCollection
    {
        return EmprestimoResource::collection($this->repository->paginar($filtros, (int) ($filtros['per_page'] ?? 15)))
            ->additional(['meta' => ['contadores' => $this->repository->contadores($filtros)]]);
    }

    /** GET /emprestimos/{emprestimo} — painel "Detalhes". */
    public function show(Emprestimo $emprestimo): EmprestimoResource
    {
        return new EmprestimoResource($this->repository->encontrar($emprestimo->id));
    }

    /** POST /emprestimos/previa — confere leitor, livro, preço e disponibilidade sem gravar. */
    public function previa(PreviaEmprestimoRequest $request): JsonResponse
    {
        $previa = $this->service->previa($request->validated());

        return response()->json(['data' => [
            ...$previa,
            'cliente' => new UsuarioResource($previa['cliente']),
            'livro' => new LivroResource($previa['livro']->loadCount('exemplaresDisponiveis as exemplares_disponiveis_count')),
            'exemplar' => $previa['exemplar'] ? new ExemplarResource($previa['exemplar']) : null,
        ]]);
    }

    /** POST /emprestimos — "Registrar empréstimo". */
    public function store(StoreEmprestimoRequest $request): JsonResponse
    {
        $emprestimo = $this->service->registrar($request->validated(), $request->user());

        return (new EmprestimoResource($emprestimo))
            ->additional(['message' => 'Empréstimo registrado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /emprestimos/{emprestimo} — renovar prazo / observações. */
    public function update(UpdateEmprestimoRequest $request, Emprestimo $emprestimo): EmprestimoResource
    {
        return (new EmprestimoResource($this->service->atualizar($emprestimo, $request->validated())))
            ->additional(['message' => 'Empréstimo atualizado com sucesso.']);
    }

    /** PATCH /emprestimos/{emprestimo}/devolucao — "Registrar devolução" (dar baixa). */
    public function devolver(DevolucaoRequest $request, Emprestimo $emprestimo): EmprestimoResource
    {
        return (new EmprestimoResource($this->service->registrarDevolucao($emprestimo->id, $request->validated())))
            ->additional(['message' => 'Devolução registrada com sucesso.']);
    }
}
