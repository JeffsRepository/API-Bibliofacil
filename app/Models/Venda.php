<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venda extends Model
{
    protected $table = 'vendas';

    protected $fillable = [
        'cliente_id', 'funcionario_id', 'forma_pagamento_id',
        'data_venda', 'subtotal', 'desconto', 'total',
    ];

    protected function casts(): array
    {
        return [
            'data_venda' => 'datetime',
            'subtotal' => 'decimal:2',
            'desconto' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'funcionario_id');
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemVenda::class);
    }
}
