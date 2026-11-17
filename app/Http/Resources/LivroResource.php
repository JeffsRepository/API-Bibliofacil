<?php

namespace App\Http\Resources;

use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Livro */
class LivroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disponiveis = (int) ($this->exemplares_disponiveis_count ?? 0);

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'isbn' => $this->isbn,
            'preco_venda' => (float) $this->preco_venda,
            'preco_emprestimo' => (float) $this->preco_emprestimo,
            // "Qtd." da tela = exemplares disponíveis (estoque calculado)
            'quantidade_disponivel' => $disponiveis,
            'quantidade_emprestada' => (int) ($this->exemplares_emprestados_count ?? 0),
            'total_exemplares' => (int) ($this->total_exemplares_count ?? 0),
            'situacao' => $disponiveis > 0 ? 'disponivel' : 'indisponivel',
            'situacao_rotulo' => $disponiveis > 0 ? 'Disponível' : 'Indisponível',
            // "Estado" da tela: o estado é de cada exemplar; aqui vai o predominante e a distribuição
            'estado' => $this->whenLoaded('exemplares', fn () => $this->estadoPredominante()),
            'estados' => $this->whenLoaded('exemplares', fn () => $this->exemplares->countBy(fn ($e) => $e->estado->value)),
            // lista completa de exemplares só no detalhe/cadastro/edição
            'exemplares' => $this->when(
                $request->routeIs('livros.show', 'livros.store', 'livros.update'),
                fn () => ExemplarResource::collection($this->exemplares)
            ),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function estadoPredominante(): ?string
    {
        $disponiveis = $this->exemplares->filter->estaDisponivel();
        $base = $disponiveis->isNotEmpty() ? $disponiveis : $this->exemplares;

        return $base->countBy(fn ($e) => $e->estado->value)->sortDesc()->keys()->first();
    }
}
