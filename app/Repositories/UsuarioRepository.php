<?php

namespace App\Repositories;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UsuarioRepository
{
    /** Filtros: tipo (cliente|funcionario|admin), busca (nome, e-mail, telefone, CPF, matrícula). */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        return $this->queryFiltrada($filtros)
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /** Contadores das abas "Todos / Clientes / Funcionários / Admins". */
    public function contadores(?string $busca = null): array
    {
        //Tipo é um array mapeado devido o pluck, ex.: ['cliente' => 30]
        $porTipo = $this->queryFiltrada(['busca' => $busca])
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return [
            'todos' => (int) $porTipo->sum(),
            'clientes' => (int) ($porTipo[TipoUsuario::Cliente->value] ?? 0),
            'funcionarios' => (int) ($porTipo[TipoUsuario::Funcionario->value] ?? 0),
            'admins' => (int) ($porTipo[TipoUsuario::Admin->value] ?? 0),
        ];
    }

    public function encontrar(int $id): Usuario
    {
        return Usuario::findOrFail($id);
    }

    public function porEmail(string $email): ?Usuario
    {
        return Usuario::where('email', $email)->first();
    }

    /** Autocomplete de leitores, já com a quantidade de pendências. */
    public function buscarClientes(?string $termo, int $limite = 10): Collection
    {
        return Usuario::clientes()
            ->when($termo, fn ($q, $t) => $this->aplicarBusca($q, $t))
            ->withCount([
                'emprestimos as emprestimos_em_aberto_count' => fn ($q) => $q->emAberto(),
                'emprestimos as emprestimos_atrasados_count' => fn ($q) => $q->atrasados(),
            ])
            ->orderBy('nome')
            ->limit($limite)
            ->get();
    }

    public function criar(array $dados): Usuario
    {
        return Usuario::create($dados);
    }

    public function atualizar(Usuario $usuario, array $dados): Usuario
    {
        $usuario->update($dados);

        return $usuario;
    }

    public function excluir(Usuario $usuario): void
    {
        $usuario->tokens()->delete();
        $usuario->delete();
    }

    public function possuiMovimentacao(Usuario $usuario): bool
    {
        return $usuario->emprestimos()->exists()
            || $usuario->compras()->exists()
            || $usuario->emprestimosRegistrados()->exists()
            || $usuario->vendasRegistradas()->exists();
    }

    public function contarClientes(): int
    {
        return Usuario::clientes()->count();
    }

    public function contarAdmins(): int
    {
        return Usuario::where('tipo', TipoUsuario::Admin)->count();
    }

    private function queryFiltrada(array $filtros): Builder
    {
        return Usuario::query()
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t)) // $t é o que tem em $filtros['tipo]
            ->when($filtros['busca'] ?? null, fn ($q, $b) => $this->aplicarBusca($q, $b));
    }

    private function aplicarBusca(Builder $q, string $busca): Builder
    {
        return $q->where(fn ($w) => $w
            ->where('nome', 'like', "%{$busca}%")
            ->orWhere('email', 'like', "%{$busca}%")
            ->orWhere('telefone', 'like', "%{$busca}%")
            ->orWhere('cpf', 'like', "%{$busca}%")
            ->orWhere('matricula', 'like', "%{$busca}%"));
    }
}
