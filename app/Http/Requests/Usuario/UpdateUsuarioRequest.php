<?php

namespace App\Http\Requests\Usuario;

use App\Enums\TipoUsuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $novoTipo = TipoUsuario::tryFrom((string) $this->input('tipo'));

        return $this->user()->can('update', [$this->route('usuario'), $novoTipo]);
    }

    public function rules(): array
    {
        $id = $this->route('usuario')->id;

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($id)],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tipo' => ['sometimes', 'required', Rule::enum(TipoUsuario::class)],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'cpf' => ['sometimes', 'nullable', 'string', 'max:14', Rule::unique('usuarios', 'cpf')->ignore($id)],
            'endereco' => ['sometimes', 'nullable', 'string', 'max:255'],
            'matricula' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('usuarios', 'matricula')->ignore($id)],
            'cargo' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Você só pode editar clientes. Funcionários e admins são gerenciados por administradores.');
    }
}
