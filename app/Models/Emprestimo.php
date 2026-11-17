<?php

namespace App\Models;

use App\Enums\StatusEmprestimo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Emprestimo extends Model
{
    protected $table = 'emprestimos';

    protected $fillable = [
        'cliente_id', 'funcionario_id', 'exemplar_id',
        'data_emprestimo', 'data_devolucao_prevista', 'data_devolucao',
        'valor_cobrado', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_emprestimo' => 'date',
            'data_devolucao_prevista' => 'date',
            'data_devolucao' => 'date',
            'valor_cobrado' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'funcionario_id');
    }

    public function exemplar(): BelongsTo
    {
        return $this->belongsTo(Exemplar::class);
    }

    /* ---------- Regras calculadas (não gravadas) ---------- */

    public function estaEmAberto(): bool
    {
        return $this->data_devolucao === null;
    }

    public function estaAtrasado(): bool
    {
        return $this->estaEmAberto() && $this->data_devolucao_prevista->lt(Carbon::today());
    }

    public function diasAtraso(): int
    {
        if (! $this->estaAtrasado()) {
            return 0;
        }

        return (int) $this->data_devolucao_prevista->diffInDays(Carbon::today());
    }

    public function status(): StatusEmprestimo
    {
        return match (true) {
            ! $this->estaEmAberto() => StatusEmprestimo::Devolvido,
            $this->estaAtrasado() => StatusEmprestimo::Atrasado,
            default => StatusEmprestimo::Ativo,
        };
    }

    /* ---------- Scopes ---------- */

    /** Em aberto: ainda não devolvido (inclui os atrasados). */
    public function scopeEmAberto(Builder $q): Builder
    {
        return $q->whereNull('data_devolucao');
    }

    /** Ativos no prazo: em aberto e com previsão hoje ou no futuro. */
    public function scopeAtivos(Builder $q): Builder
    {
        return $q->whereNull('data_devolucao')
            ->whereDate('data_devolucao_prevista', '>=', Carbon::today());
    }

    public function scopeAtrasados(Builder $q): Builder
    {
        return $q->whereNull('data_devolucao')
            ->whereDate('data_devolucao_prevista', '<', Carbon::today());
    }

    public function scopeDevolvidos(Builder $q): Builder
    {
        return $q->whereNotNull('data_devolucao');
    }
}
