<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venda\ListarVendasRequest;
use App\Http\Requests\Venda\PreviaVendaRequest;
use App\Http\Requests\Venda\StoreVendaRequest;
use App\Http\Resources\VendaResource;
use App\Models\Venda;
use App\Repositories\VendaRepository;
use App\Services\VendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendaController extends Controller
{
    public function __construct(
        private readonly VendaService $service,
        private readonly VendaRepository $repository,
    ) {}

    /** GET /vendas?de=&ate=&mes=&forma_pagamento_id=&cliente_id= */
    public function index(ListarVendasRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();

        return VendaResource::collection($this->repository->paginar($filtros, (int) ($filtros['per_page'] ?? 15)));
    }

    /** GET /vendas/{venda} — comprovante. */
    public function show(Venda $venda): VendaResource
    {
        return new VendaResource($this->repository->encontrar($venda->id));
    }

    /** POST /vendas/previa — resumo do carrinho (subtotal, desconto, total) sem gravar. */
    public function previa(PreviaVendaRequest $request): JsonResponse
    {
        $dados = $request->validated();

        return response()->json(['data' => $this->service->calcularResumo($dados['itens'], (float) ($dados['desconto'] ?? 0))]);
    }

    /** POST /vendas — "Finalizar Venda". */
    public function store(StoreVendaRequest $request): JsonResponse
    {
        $venda = $this->service->finalizar($request->validated(), $request->user());

        return (new VendaResource($venda))
            ->additional(['message' => 'Venda finalizada com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }
}
