<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmprestimoController;
use App\Http\Controllers\Api\V1\ExemplarController;
use App\Http\Controllers\Api\V1\FormaPagamentoController;
use App\Http\Controllers\Api\V1\LivroController;
use App\Http\Controllers\Api\V1\OpcoesController;
use App\Http\Controllers\Api\V1\RelatorioController;
use App\Http\Controllers\Api\V1\UsuarioController;
use App\Http\Controllers\Api\V1\VendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BIBLIOFÁCIL — API REST v1   (prefixo: /api/v1)
|--------------------------------------------------------------------------
| Níveis de acesso:
|   público ........ apenas login
|   equipe ......... funcionário + admin (auth:sanctum + tipo:funcionario,admin)
|   admin .......... somente administradores (tipo:admin)
*/

Route::prefix('v1')->group(function () {

    // ---------------- Público ----------------
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    // ---------------- Equipe (funcionário + admin) ----------------
    Route::middleware(['auth:sanctum', 'tipo:funcionario,admin'])->group(function () {

        // Autenticação / sessão
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('auth/senha', [AuthController::class, 'alterarSenha'])->name('auth.senha');

        // Dashboard + busca global + opções de selects
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('busca', [DashboardController::class, 'buscar'])->name('busca');
        Route::get('opcoes', OpcoesController::class)->name('opcoes');

        // Livros (consulta) e exemplares (consulta)
        Route::get('livros', [LivroController::class, 'index'])->name('livros.index');
        Route::get('livros/{livro}', [LivroController::class, 'show'])->name('livros.show');
        Route::get('livros/{livro}/exemplares', [ExemplarController::class, 'index'])->name('livros.exemplares.index');
        Route::get('exemplares/codigo/{codigo}', [ExemplarController::class, 'porCodigo'])->name('exemplares.codigo');
        Route::get('exemplares/{exemplar}', [ExemplarController::class, 'show'])->name('exemplares.show');

        // Empréstimos: listar, detalhar, emprestar, renovar e dar baixa
        Route::prefix('emprestimos')->name('emprestimos.')->controller(EmprestimoController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('ativos', 'ativos')->name('ativos');
            Route::get('atrasados', 'atrasados')->name('atrasados');
            Route::post('previa', 'previa')->name('previa');
            Route::post('/', 'store')->name('store');
            Route::get('{emprestimo}', 'show')->name('show');
            Route::patch('{emprestimo}', 'update')->name('update');
            Route::patch('{emprestimo}/devolucao', 'devolver')->name('devolucao');
        });

        // Clientes (select "Leitor"/"Cliente") e pendências
        Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('clientes/{cliente}/pendencias', [ClienteController::class, 'pendencias'])->name('clientes.pendencias');

        // Vendas
        Route::prefix('vendas')->name('vendas.')->controller(VendaController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('previa', 'previa')->name('previa');
            Route::post('/', 'store')->name('store');
            Route::get('{venda}', 'show')->name('show');
        });

        // Formas de pagamento (consulta)
        Route::get('formas-pagamento', [FormaPagamentoController::class, 'index'])->name('formas-pagamento.index');
        Route::get('formas-pagamento/{formaPagamento}', [FormaPagamentoController::class, 'show'])->name('formas-pagamento.show');

        // Usuários: funcionário cadastra/edita CLIENTES; admin, qualquer tipo (UsuarioPolicy)
        Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::match(['put', 'patch'], 'usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');

        // ---------------- Somente ADMIN ----------------
        Route::middleware('tipo:admin')->group(function () {

            // Acervo: cadastrar, editar e excluir títulos e exemplares
            Route::post('livros', [LivroController::class, 'store'])->name('livros.store');
            Route::match(['put', 'patch'], 'livros/{livro}', [LivroController::class, 'update'])->name('livros.update');
            Route::delete('livros/{livro}', [LivroController::class, 'destroy'])->name('livros.destroy');
            Route::post('livros/{livro}/exemplares', [ExemplarController::class, 'store'])->name('livros.exemplares.store');
            Route::match(['put', 'patch'], 'exemplares/{exemplar}', [ExemplarController::class, 'update'])->name('exemplares.update');
            Route::delete('exemplares/{exemplar}', [ExemplarController::class, 'destroy'])->name('exemplares.destroy');

            // Usuários: excluir
            Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

            // Formas de pagamento: cadastro prévio
            Route::post('formas-pagamento', [FormaPagamentoController::class, 'store'])->name('formas-pagamento.store');
            Route::match(['put', 'patch'], 'formas-pagamento/{formaPagamento}', [FormaPagamentoController::class, 'update'])->name('formas-pagamento.update');
            Route::delete('formas-pagamento/{formaPagamento}', [FormaPagamentoController::class, 'destroy'])->name('formas-pagamento.destroy');

            // Relatórios
            Route::prefix('relatorios')->name('relatorios.')->controller(RelatorioController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('resumo', 'resumo')->name('resumo');
                Route::get('livros-mais-emprestados', 'livrosMaisEmprestados')->name('livros-mais-emprestados');
                Route::get('vendas-por-pagamento', 'vendasPorPagamento')->name('vendas-por-pagamento');
                Route::get('vendas-por-mes', 'vendasPorMes')->name('vendas-por-mes');
                Route::get('emprestimos-atrasados', 'emprestimosAtrasados')->name('emprestimos-atrasados');
            });
        });
    });
});
