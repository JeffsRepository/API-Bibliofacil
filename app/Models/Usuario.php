<?php

namespace App\Models;

use App\Enums\TipoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome', 'email', 'telefone', 'tipo', 'password',
        'cpf', 'endereco', 'matricula', 'cargo',
    ];

    protected $hidden = ['password', 'remember_token'];

    // converte o formato quando sai ou quando vai p/ banco de dados
    protected function casts(): array
    {
        return [
            'tipo' => TipoUsuario::class,
            'password' => 'hashed',
        ];
    }

    public function ehAdmin(): bool
    {
        return $this->tipo === TipoUsuario::Admin;
    }

    public function ehCliente(): bool
    {
        return $this->tipo === TipoUsuario::Cliente;
    }

    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome));
        $primeira = mb_substr($partes[0] ?? '', 0, 1);
        $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

        return mb_strtoupper($primeira.$ultima);
    }

    /** Empréstimos em que este usuário é o cliente (leitor). */
    public function emprestimos(): HasMany
    {
        return $this->hasMany(Emprestimo::class, 'cliente_id');
    }

    /** Empréstimos registrados por este funcionário/admin. */
    public function emprestimosRegistrados(): HasMany
    {
        return $this->hasMany(Emprestimo::class, 'funcionario_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Venda::class, 'cliente_id');
    }

    public function vendasRegistradas(): HasMany
    {
        return $this->hasMany(Venda::class, 'funcionario_id');
    }

    // Posso usar Usuario::clientes() dai me retorna apenas o tipo Cliente.
    public function scopeClientes(Builder $query): Builder
    {
        return $query->where('tipo', TipoUsuario::Cliente);
    }

    public function scopeEquipe(Builder $query): Builder
    {
        return $query->whereIn('tipo', [TipoUsuario::Funcionario, TipoUsuario::Admin]);
    }
}
