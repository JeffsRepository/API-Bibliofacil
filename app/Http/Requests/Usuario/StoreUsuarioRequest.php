<?php

namespace App\Http\Requests\Usuario;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    /** Só admin cria funcionário/admin; funcionário cria apenas clientes (UsuarioPolicy). */
    public function authorize(): bool
    {
        $tipo = TipoUsuario::tryFrom((string) $this->input('tipo'));

        return $tipo === null || $this->user()->can('create', [Usuario::class, $tipo]);
    }

    public function rules(): array
    {
        $equipe = [TipoUsuario::Funcionario->value, TipoUsuario::Admin->value];

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', Rule::enum(TipoUsuario::class)],
            // senha: obrigatória para funcionário/admin, proibida para cliente
            'password' => [Rule::requiredIf(in_array($this->input('tipo'), $equipe, true)), 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'cpf' => ['nullable', 'string', 'max:14', 'unique:usuarios,cpf'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'matricula' => ['nullable', 'string', 'max:30', 'unique:usuarios,matricula'],
            'cargo' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Apenas administradores podem cadastrar funcionários e outros administradores.');
    }
}
