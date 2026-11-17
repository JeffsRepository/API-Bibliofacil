<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\EmprestimoRepository;
use App\Repositories\ExemplarRepository;
use App\Repositories\UsuarioRepository;

class DashboardService
{
    public function __construct(
        private readonly ExemplarRepository $exemplares,
        private readonly EmprestimoRepository $emprestimos,
        private readonly UsuarioRepository $usuarios,
    ) {}

    public function montar(Usuario $logado): array
    {
        return [
            'indicadores' => [
                'livros_disponiveis' => $this->exemplares->totalDisponiveis(),
                'emprestimos_ativos' => $this->emprestimos->contarAtivos(),
                'emprestimos_em_atraso' => $this->emprestimos->contarAtrasados(),
                'clientes_cadastrados' => $this->usuarios->contarClientes(),
            ],
            'atalhos' => $this->atalhos($logado),
        ];
    }

    /** Cards de atalho do dashboard, já filtrados pela permissão de quem está logado. */
    private function atalhos(Usuario $logado): array
    {
        $atalhos = [
            ['chave' => 'emprestar_livro', 'titulo' => 'Emprestar Livro', 'descricao' => 'Registrar um novo empréstimo de livro para um cliente.', 'metodo' => 'POST', 'endpoint' => '/api/v1/emprestimos', 'somente_admin' => false],
            ['chave' => 'ver_emprestimos', 'titulo' => 'Ver Empréstimos', 'descricao' => 'Consultar empréstimos ativos, atrasados e o histórico.', 'metodo' => 'GET', 'endpoint' => '/api/v1/emprestimos', 'somente_admin' => false],
            ['chave' => 'cadastrar_livro', 'titulo' => 'Cadastrar Livro', 'descricao' => 'Adicionar um novo livro ao acervo (título, autor, ISBN).', 'metodo' => 'POST', 'endpoint' => '/api/v1/livros', 'somente_admin' => true],
            ['chave' => 'cadastrar_usuario', 'titulo' => 'Cadastrar Usuário', 'descricao' => 'Registrar novos clientes e funcionários da biblioteca.', 'metodo' => 'POST', 'endpoint' => '/api/v1/usuarios', 'somente_admin' => false],
            ['chave' => 'realizar_venda', 'titulo' => 'Realizar Venda', 'descricao' => 'Registrar a venda de livros e o método de pagamento.', 'metodo' => 'POST', 'endpoint' => '/api/v1/vendas', 'somente_admin' => false],
            ['chave' => 'ver_relatorios', 'titulo' => 'Ver Relatórios', 'descricao' => 'Acompanhar empréstimos, atrasos e vendas por período.', 'metodo' => 'GET', 'endpoint' => '/api/v1/relatorios', 'somente_admin' => true],
        ];

        return array_values(array_filter(
            $atalhos,
            fn (array $a) => ! $a['somente_admin'] || $logado->ehAdmin()
        ));
    }
}
