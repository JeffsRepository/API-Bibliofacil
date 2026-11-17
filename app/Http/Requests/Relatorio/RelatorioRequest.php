<?php

namespace App\Http\Requests\Relatorio;

use App\Support\Periodo;
use Illuminate\Foundation\Http\FormRequest;

/** Filtros de período: ?de=2026-10-01&ate=2026-10-31 ou ?mes=2026-10 */
class RelatorioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'mes' => ['nullable', 'date_format:Y-m'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function periodo(): Periodo
    {
        return Periodo::deArray($this->validated());
    }

    public function limite(): int
    {
        return (int) ($this->validated('limite') ?? 10);
    }
}
