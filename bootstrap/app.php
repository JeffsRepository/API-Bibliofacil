<?php

use App\Http\Middleware\VerificarTipoUsuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tipo' => VerificarTipoUsuario::class,
        ]);

        // API stateless: nunca redirecionar para uma tela de login
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toda rota /api/* responde em JSON, mesmo sem o header Accept
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Não autenticado. Faça login para continuar.'], 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $previa = $e->getPrevious();
                $mensagem = $previa instanceof AuthorizationException && $previa->getMessage() !== 'This action is unauthorized.'
                    ? $previa->getMessage()
                    : 'Você não tem permissão para realizar esta ação.';

                return response()->json(['message' => $mensagem], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $mensagem = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'Registro não encontrado.'
                    : ($e->getMessage() ?: 'Rota não encontrada.');

                return response()->json(['message' => $mensagem], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Método HTTP não permitido para esta rota.'], 405);
            }
        });
    })->create();
