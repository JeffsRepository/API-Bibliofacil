<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Violação de regra de negócio (ex.: exemplar indisponível, cliente com atraso).
 * Renderizada automaticamente como JSON 422.
 */
class RegraNegocioException extends Exception
{
    public function __construct(string $message, private readonly array $erros = [], int $code = 422)
    {
        parent::__construct($message, $code);
    }

    /** Regra de negócio não é erro do sistema: não polui o log. */
    public function report(): bool
    {
        return false;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => (object) $this->erros,
        ], $this->getCode() ?: 422);
    }
}
