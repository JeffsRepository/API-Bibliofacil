<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Relatorio\RelatorioRequest;
use App\Services\RelatorioService;
use Illuminate\Http\JsonResponse;

/** Todos os relatórios aceitam ?de=YYYY-MM-DD&ate=YYYY-MM-DD ou ?mes=YYYY-MM. Acesso: admin. */
class RelatorioController extends Controller
{
    public function __construct(private readonly RelatorioService $relatorios) {}

    /** GET /relatorios — tela completa em uma chamada. */
    public function index(RelatorioRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->relatorios->completo($request->periodo(), $request->limite())]);
    }

    /** GET /relatorios/resumo — cards: realizados, devolvidos, em atraso, vendas. */
    public function resumo(RelatorioRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->relatorios->resumo($request->periodo())]);
    }

    /** GET /relatorios/livros-mais-emprestados */
    public function livrosMaisEmprestados(RelatorioRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->relatorios->livrosMaisEmprestados($request->periodo(), $request->limite()),
            'meta' => ['periodo' => $request->periodo()->toArray()],
        ]);
    }

    /** GET /relatorios/vendas-por-pagamento */
    public function vendasPorPagamento(RelatorioRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->relatorios->vendasPorFormaPagamento($request->periodo())]);
    }

    /** GET /relatorios/vendas-por-mes */
    public function vendasPorMes(RelatorioRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->relatorios->vendasPorMes($request->periodo())]);
    }

    /** GET /relatorios/emprestimos-atrasados */
    public function emprestimosAtrasados(RelatorioRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->relatorios->emprestimosEmAtraso((int) ($request->validated('limite') ?? 50))]);
    }
}
