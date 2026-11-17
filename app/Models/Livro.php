<?php

namespace App\Models;

use App\Enums\SituacaoExemplar;
use Database\Factories\LivroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Livro extends Model
{
    /** @use HasFactory<LivroFactory> */
    use HasFactory;

    protected $table = 'livros';

    protected $fillable = ['isbn', 'titulo', 'autor', 'preco_venda', 'preco_emprestimo'];

    protected function casts(): array
    {
        return [
            'preco_venda' => 'decimal:2',
            'preco_emprestimo' => 'decimal:2',
        ];
    }

    // Aqui trás todos os exemplares:
    public function exemplares(): HasMany
    {
        return $this->hasMany(Exemplar::class);
    }

    // Aqui somente os disponíveis:
    public function exemplaresDisponiveis(): HasMany
    {
        return $this->exemplares()->where('situacao', SituacaoExemplar::Disponivel);
    }

    public function emprestimos(): HasManyThrough
    {
        return $this->hasManyThrough(Emprestimo::class, Exemplar::class);
    }

    /** Estoque = contagem de exemplares disponíveis (não existe coluna de quantidade). */
    public function estoque(): int
    {
        return $this->exemplares_disponiveis_count ?? $this->exemplaresDisponiveis()->count();
    }
}
