<?php

namespace App\Services;

use App\Exceptions\RegraNegocioException;
use App\Models\FormaPagamento;
use App\Repositories\FormaPagamentoRepository;
use Illuminate\Support\Str;

class FormaPagamentoService
{
    public function __construct(private readonly FormaPagamentoRepository $formas) {}

    public function cadastrar(array $dados): FormaPagamento
    {
        return $this->formas->criar($dados + ['slug' => Str::slug($dados['nome'], '_')]);
    }

    public function atualizar(FormaPagamento $forma, array $dados): FormaPagamento
    {
        if (isset($dados['nome'])) {
            $dados['slug'] = Str::slug($dados['nome'], '_');
        }

        return $this->formas->atualizar($forma, $dados);
    }

    /** Formas já usadas em vendas não são apagadas: devem ser desativadas. */
    public function excluir(FormaPagamento $forma): void
    {
        if ($this->formas->possuiVendas($forma)) {
            throw new RegraNegocioException('Esta forma de pagamento já foi usada em vendas. Desative-a em vez de excluir.');
        }

        $this->formas->excluir($forma);
    }
}
