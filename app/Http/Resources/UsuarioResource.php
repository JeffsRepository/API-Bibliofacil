<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Usuario */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ehCliente = $this->ehCliente();

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'iniciais' => $this->iniciais(),
            'email' => $this->email,
            'telefone' => $this->telefone,
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'cpf' => $this->when($ehCliente, $this->cpf),
            'endereco' => $this->when($ehCliente, $this->endereco),
            'matricula' => $this->when(! $ehCliente, $this->matricula),
            'cargo' => $this->when(! $ehCliente, $this->cargo),
            'pendencias' => $this->when(isset($this->emprestimos_em_aberto_count), fn () => [
                'emprestimos_em_aberto' => (int) $this->emprestimos_em_aberto_count,
                'emprestimos_atrasados' => (int) $this->emprestimos_atrasados_count,
                'possui_pendencias' => $this->emprestimos_em_aberto_count > 0,
            ]),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
