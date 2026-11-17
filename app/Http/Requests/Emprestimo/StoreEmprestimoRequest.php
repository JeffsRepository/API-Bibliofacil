<?php

namespace App\Http\Requests\Emprestimo;

use App\Enums\TipoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Novo empréstimo. O exemplar pode ser escolhido:
 * - pelo código de barras (bipado), ou
 * - pelo livro_id: o sistema pega qualquer unidade disponível do título.
 */
class StoreEmprestimoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // cliente_id deve apontar para um usuário do tipo "cliente" (checado aqui, não no banco)
            'cliente_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('tipo', TipoUsuario::Cliente->value)],
            'livro_id' => ['required_without:codigo_barras', 'nullable', 'integer', 'exists:livros,id'],
            'codigo_barras' => ['required_without:livro_id', 'nullable', 'string', 'exists:exemplares,codigo_barras'],
            'data_devolucao_prevista' => $this->regraDataPrevista(),
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function regraDataPrevista(): array
    {
        return ['required', 'date', 'after:today'];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists' => 'O leitor selecionado não é um cliente cadastrado.',
            'data_devolucao_prevista.after' => 'A devolução prevista deve ser uma data futura.',
        ];
    }
}
