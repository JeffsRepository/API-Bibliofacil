<?php

namespace App\Models;

use App\Enums\EstadoExemplar;
use App\Enums\SituacaoExemplar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exemplar extends Model
{
    use HasFactory;

    protected $table = 'exemplares';

    protected $fillable = ['livro_id', 'codigo_barras', 'estado', 'situacao'];

    protected $attributes = [
        'situacao' => 'disponivel',
        'estado' => 'bom',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoExemplar::class,
            'situacao' => SituacaoExemplar::class,
        ];
    }

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }

    public function emprestimos(): HasMany
    {
        return $this->hasMany(Emprestimo::class);
    }

    public function itemVenda(): HasOne
    {
        return $this->hasOne(ItemVenda::class);
    }

    public function estaDisponivel(): bool
    {
        return $this->situacao === SituacaoExemplar::Disponivel;
    }

    public function possuiMovimentacao(): bool
    {
        return $this->emprestimos()->exists() || $this->itemVenda()->exists();
    }
}
