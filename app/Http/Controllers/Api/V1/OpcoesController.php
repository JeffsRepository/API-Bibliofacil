<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoExemplar;
use App\Enums\SituacaoExemplar;
use App\Enums\TipoUsuario;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /opcoes — valores dos selects (tipos de usuário, estados e situações de exemplar). */
class OpcoesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tipos = collect(TipoUsuario::cases())
            ->filter(fn ($t) => $request->user()->ehAdmin() || $t === TipoUsuario::Cliente);

        $mapear = fn ($casos) => collect($casos)->map(fn ($c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()])->values();

        return response()->json(['data' => [
            'tipos_usuario' => $mapear($tipos),
            'estados_exemplar' => $mapear(EstadoExemplar::cases()),
            'situacoes_exemplar' => $mapear(SituacaoExemplar::cases()),
            'prazo_emprestimo_padrao_dias' => (int) config('bibliofacil.prazo_emprestimo_dias'),
        ]]);
    }
}
