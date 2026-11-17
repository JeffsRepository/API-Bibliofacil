<?php

namespace App\Http\Resources;

use App\Models\Emprestimo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Emprestimo */
class EmprestimoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status();
        $exemplar = $this->exemplar;
        $livro = $exemplar?->livro;

        return [
            'id' => $this->id,
            'leitor' => $this->whenLoaded('cliente', fn () => [
                'id' => $this->cliente->id,
                'nome' => $this->cliente->nome,
                'email' => $this->cliente->email,
                'telefone' => $this->cliente->telefone,
            ]),
            'livro' => $livro ? [
                'id' => $livro->id,
                'titulo' => $livro->titulo,
                'autor' => $livro->autor,
                'isbn' => $livro->isbn,
            ] : null,
            'exemplar' => $exemplar ? [
                'id' => $exemplar->id,
                'codigo_barras' => $exemplar->codigo_barras,
                'estado' => $exemplar->estado->value,
                'estado_rotulo' => $exemplar->estado->rotulo(),
            ] : null,
            'data_emprestimo' => $this->data_emprestimo->toDateString(),
            'data_devolucao_prevista' => $this->data_devolucao_prevista->toDateString(),
            'data_devolucao' => $this->data_devolucao?->toDateString(),
            'devolvido' => ! $this->estaEmAberto(),
            'situacao' => $status->value,
            'situacao_rotulo' => $status->rotulo(),
            'dias_atraso' => $this->diasAtraso(),
            'valor_cobrado' => (float) $this->valor_cobrado,
            'observacoes' => $this->observacoes,
            'registrado_por' => $this->whenLoaded('funcionario', fn () => [
                'id' => $this->funcionario->id,
                'nome' => $this->funcionario->nome,
            ]),
            'acoes' => [
                'pode_devolver' => $this->estaEmAberto(),
                'pode_renovar' => $this->estaEmAberto(),
            ],
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
