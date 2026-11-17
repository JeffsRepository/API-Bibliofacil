<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Value object de período usado nos filtros de relatórios e listagens.
 * Aceita ?de=YYYY-MM-DD&ate=YYYY-MM-DD ou ?mes=YYYY-MM.
 */
final class Periodo
{
    public function __construct(
        public readonly ?CarbonImmutable $inicio,
        public readonly ?CarbonImmutable $fim,
    ) {}

    public static function deArray(array $dados): self
    {
        if (! empty($dados['mes'])) {
            $mes = CarbonImmutable::createFromFormat('Y-m', $dados['mes'])->startOfMonth();

            return new self($mes, $mes->endOfMonth());
        }

        return new self(
            ! empty($dados['de']) ? CarbonImmutable::parse($dados['de'])->startOfDay() : null,
            ! empty($dados['ate']) ? CarbonImmutable::parse($dados['ate'])->endOfDay() : null,
        );
    }

    public function toArray(): array
    {
        return [
            'de' => $this->inicio?->toDateString(),
            'ate' => $this->fim?->toDateString(),
        ];
    }
}
