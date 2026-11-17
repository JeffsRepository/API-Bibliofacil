<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota a determinados tipos de usuário.
 * Uso: ->middleware('tipo:admin') ou ->middleware('tipo:funcionario,admin')
 */
class VerificarTipoUsuario
{
    public function handle(Request $request, Closure $next, string ...$tipos): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! in_array($usuario->tipo->value, $tipos, true)) {
            return response()->json([
                'message' => 'Acesso negado. Esta ação é permitida apenas para: '.implode(', ', $tipos).'.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
