<?php

namespace App\Services;

use App\Enums\SituacaoExemplar;
use App\Enums\TipoUsuario;
use App\Exceptions\RegraNegocioException;
use App\Models\Emprestimo;
use App\Models\Exemplar;
use App\Models\Livro;
use App\Models\Usuario;
use App\Repositories\EmprestimoRepository;
use App\Repositories\ExemplarRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmprestimoService
{
    public function __construct(
        private readonly EmprestimoRepository $emprestimos,
        private readonly ExemplarRepository $exemplares,
        private readonly UsuarioRepository $usuarios,
    ) {}

    /** Pendências do leitor (mostradas ao digitar o nome no formulário de novo empréstimo). */
    public function pendenciasDoCliente(Usuario $cliente): array
    {
        $abertos = $this->emprestimos->emAbertoDoCliente($cliente->id);

        // Aqui o LARAVEL percorre cada item da collection $abertos e chama o metodo estaAtrasado() e mantem na lista apenas os itens que o retorno foi true. O papel do values aqui é reordenar os indices do collection que ficaram como true. Ex.: [0], [1], [2] ...
        $atrasados = $abertos->filter->estaAtrasado()->values();
        $motivos = $this->motivosBloqueio($abertos->count(), $atrasados->count());

        return [
            'cliente' => $cliente,
            'possui_pendencias' => $abertos->isNotEmpty(),
            'total_em_aberto' => $abertos->count(),
            'total_atrasados' => $atrasados->count(),
            'pode_emprestar' => empty($motivos),
            'motivos_bloqueio' => $motivos,
            'emprestimos_em_aberto' => $abertos,
        ];
    }

    /**
     * Pré-visualização (sem gravar): dados do leitor, do livro/exemplar, preço e se é possível emprestar.
     * Alimenta o painel lateral da tela antes de clicar em "Registrar empréstimo".
     */
    public function previa(array $dados): array
    {
        $cliente = $this->usuarios->encontrar($dados['cliente_id']);
        $pendencias = $this->pendenciasDoCliente($cliente);
        // Aqui é retornado o livro bem como um exemplar disponivel para emprestimo com seu devido relacionamento.
        [$livro, $exemplar] = $this->resolverLivroExemplar($dados, bloquear: false);

        $motivos = $pendencias['motivos_bloqueio'];
        if (! $exemplar) {
            $motivos[] = 'Não há exemplar disponível deste livro.';
        }

        return [
            'cliente' => $cliente,
            'pendencias' => [
                'total_em_aberto' => $pendencias['total_em_aberto'],
                'total_atrasados' => $pendencias['total_atrasados'],
            ],
            'livro' => $livro,
            'exemplar' => $exemplar,
            'valor_cobrado' => (float) $livro->preco_emprestimo,
            'data_emprestimo' => Carbon::today()->toDateString(),
            'data_devolucao_prevista' => $dados['data_devolucao_prevista']
                ?? Carbon::today()->addDays(config('bibliofacil.prazo_emprestimo_dias'))->toDateString(),
            'pode_emprestar' => empty($motivos),
            'motivos_bloqueio' => $motivos,
        ];
    }

    /**
     * Registra o empréstimo: valida o leitor, trava o exemplar (lockForUpdate),
     * grava o empréstimo copiando o preço do momento e marca o exemplar como emprestado.
     */
    public function registrar(array $dados, Usuario $responsavel): Emprestimo
    {
        $emprestimo = DB::transaction(function () use ($dados, $responsavel) {
            $cliente = $this->usuarios->encontrar($dados['cliente_id']);
            $this->garantirCliente($cliente);

            $pendencias = $this->pendenciasDoCliente($cliente);
            if (! $pendencias['pode_emprestar']) {
                throw new RegraNegocioException('O leitor possui pendências e não pode realizar novo empréstimo.', [
                    'cliente_id' => $pendencias['motivos_bloqueio'],
                ]);
            }

            [$livro, $exemplar] = $this->resolverLivroExemplar($dados, bloquear: true);
            if (! $exemplar) {
                throw new RegraNegocioException('Não há exemplar disponível para empréstimo.', [
                    'livro_id' => ['Todos os exemplares deste livro estão emprestados ou vendidos.'],
                ]);
            }

            $emprestimo = $this->emprestimos->criar([
                'cliente_id' => $cliente->id,
                'funcionario_id' => $responsavel->id,
                'exemplar_id' => $exemplar->id,
                'data_emprestimo' => Carbon::today(),
                'data_devolucao_prevista' => $dados['data_devolucao_prevista'],
                'valor_cobrado' => $livro->preco_emprestimo,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            $this->exemplares->alterarSituacao($exemplar, SituacaoExemplar::Emprestado);

            return $emprestimo;
        });

        return $this->emprestimos->encontrar($emprestimo->id);
    }

    /** Dá baixa no empréstimo: grava a data de devolução e o exemplar volta a "disponivel". */
    public function registrarDevolucao(int $emprestimoId, array $dados): Emprestimo
    {
        DB::transaction(function () use ($emprestimoId, $dados) {
            $emprestimo = $this->emprestimos->encontrarParaAtualizar($emprestimoId);

            if (! $emprestimo->estaEmAberto()) {
                throw new RegraNegocioException('Este empréstimo já foi devolvido.');
            }

            $dataDevolucao = isset($dados['data_devolucao']) ? Carbon::parse($dados['data_devolucao']) : Carbon::today();
            if ($dataDevolucao->lt($emprestimo->data_emprestimo)) { // lt = less than = menor que
                throw new RegraNegocioException('A data de devolução não pode ser anterior à retirada.', [
                    'data_devolucao' => ['Informe uma data igual ou posterior a '.$emprestimo->data_emprestimo->format('d/m/Y').'.'],
                ]);
            }

            $observacoes = trim(implode("\n", array_filter([$emprestimo->observacoes, $dados['observacoes'] ?? null])));

            $this->emprestimos->atualizar($emprestimo, [
                'data_devolucao' => $dataDevolucao,
                'observacoes' => $observacoes ?: null,
            ]);

            $exemplar = $emprestimo->exemplar()->lockForUpdate()->first();
            $exemplar->situacao = SituacaoExemplar::Disponivel;
            if (! empty($dados['estado'])) {
                $exemplar->estado = $dados['estado']; // estado de conservação conferido na devolução
            }
            $exemplar->save();
        });

        return $this->emprestimos->encontrar($emprestimoId);
    }

    /** Atualiza prazo/observações de um empréstimo em aberto (ex.: renovação). */
    public function atualizar(Emprestimo $emprestimo, array $dados): Emprestimo
    {
        if (! $emprestimo->estaEmAberto()) {
            throw new RegraNegocioException('Empréstimos devolvidos não podem ser alterados.');
        }

        $this->emprestimos->atualizar($emprestimo, $dados);

        return $this->emprestimos->encontrar($emprestimo->id);
    }

    /**
     * O exemplar pode vir pelo código de barras (bipado) ou o sistema pega
     * qualquer unidade disponível do título informado.
     *
     * @return array{0: Livro, 1: ?Exemplar}
     */
    private function resolverLivroExemplar(array $dados, bool $bloquear): array
    {
        if (! empty($dados['codigo_barras'])) {
            $exemplar = $bloquear
                ? $this->exemplares->bloquearPorCodigo($dados['codigo_barras']) // se bloquear for true envolve transação
                : $this->exemplares->porCodigoBarras($dados['codigo_barras']); // se bloquear for false ta só pesquisando o exemplar

            if (! $exemplar) {
                throw new RegraNegocioException('Exemplar não encontrado.', ['codigo_barras' => ['Código de barras inexistente.']], 404);
            }

            $livro = $exemplar->livro;

            return [$livro, $exemplar->estaDisponivel() ? $exemplar->setRelation('livro', $livro) : null];
        }

        $livro = Livro::findOrFail($dados['livro_id']);
        $exemplar = $bloquear
            ? $this->exemplares->bloquearDisponiveis($livro->id, 1)->first()
            : $livro->exemplaresDisponiveis()->orderBy('id')->first();

        return [$livro, $exemplar?->setRelation('livro', $livro)];
    }

    private function garantirCliente(Usuario $usuario): void
    {
        if ($usuario->tipo !== TipoUsuario::Cliente) {
            throw new RegraNegocioException('O leitor informado não é um cliente.', ['cliente_id' => ['Selecione um usuário do tipo cliente.']]);
        }
    }

    private function motivosBloqueio(int $emAberto, int $atrasados): array
    {
        /**
         * O config é uma função global helper do laravel usada para ler arquivos de configuração do meu sistema
         * O formato de ponto config('bibliofacil.bloquear_cliente_com_atraso')) serve para indicar o nome do arquivo e a chave q vc 
         * quer ler lá dentro. Isso evita deixar valores fixos (herdcoded) no código permitindo mudar as regras do sistema sem mexer 
         * na lógica do PHP.
         */

        $motivos = [];

        if ($atrasados > 0 && config('bibliofacil.bloquear_cliente_com_atraso')) {
            $motivos[] = "O leitor possui {$atrasados} empréstimo(s) em atraso.";
        }

        $limite = (int) config('bibliofacil.limite_emprestimos_por_cliente');

        if ($limite > 0 && $emAberto >= $limite) {
            $motivos[] = "O leitor atingiu o limite de {$limite} empréstimo(s) em aberto.";
        }

        return $motivos;
    }
}
