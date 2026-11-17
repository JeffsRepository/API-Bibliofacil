<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmprestimoResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use App\Services\EmprestimoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Endpoints de apoio aos selects "Leitor" / "Selecionar cliente" das telas de empréstimo e venda. */
class ClienteController extends Controller
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly EmprestimoService $emprestimos,
    ) {}

    /** GET /clientes?busca=ana — autocomplete já com a contagem de pendências. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return UsuarioResource::collection($this->usuarios->buscarClientes($dados['busca'] ?? null, (int) ($dados['limite'] ?? 10)));
    }

    /** GET /clientes/{cliente}/pendencias — empréstimos em aberto/atrasados do leitor. */
    public function pendencias(Usuario $cliente): JsonResponse
    {
        abort_unless($cliente->ehCliente(), 404, 'Cliente não encontrado.');

        $p = $this->emprestimos->pendenciasDoCliente($cliente);

        return response()->json(['data' => [
            ...$p,
            'cliente' => new UsuarioResource($p['cliente']),
            'emprestimos_em_aberto' => EmprestimoResource::collection($p['emprestimos_em_aberto']),
        ]]);
    }
}
