<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormaPagamento extends Model
{
    protected $table = 'formas_pagamento';

    protected $fillable = ['nome', 'slug', 'ativo'];

    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(Venda::class);
    }

    // Usa-se: FormaPagamento::ativas() em vez de a todo momento usar FormaPagamento::where('ativo', true). Se precisa alterar chamo apenas esse metodo.
    public function scopeAtivas(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }
}
