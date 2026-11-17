<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormaPagamento\SalvarFormaPagamentoRequest;
use App\Http\Resources\FormaPagamentoResource;
use App\Models\FormaPagamento;
use App\Repositories\FormaPagamentoRepository;
use App\Services\FormaPagamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormaPagamentoController extends Controller
{
    public function __construct(
        private readonly FormaPagamentoService $service,
        private readonly FormaPagamentoRepository $repository,
    ) {}

    /** GET /formas-pagamento?ativas=1 */
    public function index(Request $request): AnonymousResourceCollection
    {
        return FormaPagamentoResource::collection($this->repository->listar($request->boolean('ativas')));
    }

    public function show(FormaPagamento $formaPagamento): FormaPagamentoResource
    {
        return new FormaPagamentoResource($formaPagamento);
    }

    /** POST /formas-pagamento (admin) */
    public function store(SalvarFormaPagamentoRequest $request): JsonResponse
    {
        return (new FormaPagamentoResource($this->service->cadastrar($request->validated())))
            ->additional(['message' => 'Forma de pagamento cadastrada com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /** PUT/PATCH /formas-pagamento/{forma} (admin) — inclusive ativar/desativar. */
    public function update(SalvarFormaPagamentoRequest $request, FormaPagamento $formaPagamento): FormaPagamentoResource
    {
        return (new FormaPagamentoResource($this->service->atualizar($formaPagamento, $request->validated())))
            ->additional(['message' => 'Forma de pagamento atualizada com sucesso.']);
    }

    /** DELETE /formas-pagamento/{forma} (admin) */
    public function destroy(FormaPagamento $formaPagamento): JsonResponse
    {
        $this->service->excluir($formaPagamento);

        return response()->json(['message' => 'Forma de pagamento excluída com sucesso.']);
    }
}
