<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\Money;
use App\Helpers\Validator;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

final class TransactionService
{
    private const TIPOS_VALIDOS = ['receita', 'despesa', 'transferencia'];
    private const STATUS_VALIDOS = ['pendente', 'pago', 'cancelado'];
    private const RECORRENCIAS_VALIDAS = ['semanal', 'mensal', 'anual'];
    private const OCORRENCIAS_RECORRENCIA = 12;

    private const COLUNAS_ORDENACAO = [
        'data' => 'data',
        'valor' => 'valor',
        'descricao' => 'descricao',
        'criado_em' => 'criado_em',
    ];

    private TransactionRepository $transacoes;
    private AccountRepository $contas;
    private CategoryRepository $categorias;

    public function __construct(
        ?TransactionRepository $transacoes = null,
        ?AccountRepository $contas = null,
        ?CategoryRepository $categorias = null
    ) {
        $this->transacoes = $transacoes ?? new TransactionRepository();
        $this->contas = $contas ?? new AccountRepository();
        $this->categorias = $categorias ?? new CategoryRepository();
    }

    public function listar(int $userId, array $filtrosBrutos, string $ordenarPor, string $direcao, int $pagina, int $porPagina): array
    {
        $coluna = self::COLUNAS_ORDENACAO[$ordenarPor] ?? 'data';
        $direcaoSql = strtoupper($direcao) === 'ASC' ? 'ASC' : 'DESC';

        $filtros = [
            'search' => $filtrosBrutos['search'] ?? null,
            'tipo' => Validator::dentroDe((string) ($filtrosBrutos['tipo'] ?? ''), self::TIPOS_VALIDOS) ? $filtrosBrutos['tipo'] : null,
            'status' => Validator::dentroDe((string) ($filtrosBrutos['status'] ?? ''), self::STATUS_VALIDOS) ? $filtrosBrutos['status'] : null,
            'category_id' => !empty($filtrosBrutos['category_id']) ? (int) $filtrosBrutos['category_id'] : null,
            'account_id' => !empty($filtrosBrutos['account_id']) ? (int) $filtrosBrutos['account_id'] : null,
            'data_inicio' => $filtrosBrutos['data_inicio'] ?? null,
            'data_fim' => $filtrosBrutos['data_fim'] ?? null,
            'valor_min' => isset($filtrosBrutos['valor_min']) && $filtrosBrutos['valor_min'] !== '' ? (float) $filtrosBrutos['valor_min'] : null,
            'valor_max' => isset($filtrosBrutos['valor_max']) && $filtrosBrutos['valor_max'] !== '' ? (float) $filtrosBrutos['valor_max'] : null,
        ];

        $pagina = max($pagina, 1);
        $porPagina = min(max($porPagina, 1), 100);

        return $this->transacoes->listarPaginado($userId, $filtros, ['coluna' => $coluna, 'direcao' => $direcaoSql], $pagina, $porPagina);
    }

    public function obter(int $id, int $userId): array
    {
        $transacao = $this->transacoes->buscarPorIdEUsuario($id, $userId);

        if ($transacao === null) {
            throw new InvalidArgumentException('Transação não encontrada.', 404);
        }

        return $transacao;
    }

    /**
     * Cria uma transação simples, uma transferência (2 pernas atômicas),
     * uma série recorrente (12 ocorrências) ou um parcelamento avulso —
     * nunca mais de um desses ao mesmo tempo.
     */
    public function criar(int $userId, array $dados): array
    {
        $tipo = (string) ($dados['tipo'] ?? 'despesa');

        if ($tipo === 'transferencia') {
            return $this->criarTransferencia($userId, $dados);
        }

        $recorrenciaTipo = $dados['recorrencia_tipo'] ?? null;
        $parcelaTotal = !empty($dados['parcela_total']) ? (int) $dados['parcela_total'] : 1;

        if ($recorrenciaTipo && $parcelaTotal > 1) {
            throw new InvalidArgumentException('Uma transação não pode ser recorrente e parcelada ao mesmo tempo.');
        }

        if ($recorrenciaTipo) {
            return $this->criarRecorrente($userId, $dados);
        }

        if ($parcelaTotal > 1) {
            return $this->criarParcelada($userId, $dados);
        }

        $comum = $this->validarComum($userId, $dados);
        $id = $this->transacoes->criar($userId, $this->paraLinha($comum));

        return $this->obter($id, $userId);
    }

    public function atualizar(int $id, int $userId, array $dados): array
    {
        $atual = $this->obter($id, $userId);

        if ($atual['tipo'] === 'transferencia') {
            throw new InvalidArgumentException('Transferências não podem ser editadas diretamente — exclua e crie uma nova.');
        }

        $comum = $this->validarComum($userId, $dados, $atual['tipo']);

        if (!$this->transacoes->atualizar($id, $userId, $this->paraLinhaAtualizacao($comum))) {
            throw new InvalidArgumentException('Transação não encontrada.', 404);
        }

        return $this->obter($id, $userId);
    }

    public function excluir(int $id, int $userId): void
    {
        $transacao = $this->obter($id, $userId);

        if ($transacao['tipo'] === 'transferencia' && $transacao['transferencia_par_id']) {
            $conexao = $this->transacoes->conexao();

            try {
                $conexao->beginTransaction();
                $this->transacoes->excluir($id, $userId);
                $this->transacoes->excluir((int) $transacao['transferencia_par_id'], $userId);
                $conexao->commit();
            } catch (PDOException $e) {
                $conexao->rollBack();
                error_log('[TransactionService::excluir transferência] ' . $e->getMessage());

                throw new RuntimeException('Não foi possível excluir a transferência.', 0, $e);
            }

            return;
        }

        if (!$this->transacoes->excluir($id, $userId)) {
            throw new InvalidArgumentException('Transação não encontrada.', 404);
        }
    }

    private function criarTransferencia(int $userId, array $dados): array
    {
        $contaOrigemId = !empty($dados['account_id']) ? (int) $dados['account_id'] : 0;
        $contaDestinoId = !empty($dados['conta_destino_id']) ? (int) $dados['conta_destino_id'] : 0;

        if ($contaOrigemId === $contaDestinoId) {
            throw new InvalidArgumentException('Selecione duas contas diferentes para a transferência.');
        }

        $this->exigirContaDoUsuario($contaOrigemId, $userId);
        $this->exigirContaDoUsuario($contaDestinoId, $userId);

        $valor = Money::normalizarEntrada((string) ($dados['valor'] ?? ''));
        if (!Money::ehPositivo($valor)) {
            throw new InvalidArgumentException('O valor da transferência precisa ser positivo.');
        }

        $data = $this->validarData($dados['data'] ?? null);
        $descricao = trim((string) ($dados['descricao'] ?? 'Transferência entre contas'));

        $linhaBase = [
            'category_id' => null,
            'tipo' => 'transferencia',
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $data,
            'vencimento' => null,
            'status' => 'pago',
            'observacao' => !empty($dados['observacao']) ? trim((string) $dados['observacao']) : null,
            'recorrencia_tipo' => null,
            'recorrencia_grupo_id' => null,
            'parcela_atual' => null,
            'parcela_total' => null,
            'parcela_grupo_id' => null,
        ];

        $conexao = $this->transacoes->conexao();

        try {
            $conexao->beginTransaction();

            $idOrigem = $this->transacoes->criar($userId, array_merge($linhaBase, [
                'account_id' => $contaOrigemId,
                'direcao' => 'saida',
            ]));

            $idDestino = $this->transacoes->criar($userId, array_merge($linhaBase, [
                'account_id' => $contaDestinoId,
                'direcao' => 'entrada',
            ]));

            $this->transacoes->definirParTransferencia($idOrigem, $idDestino);
            $this->transacoes->definirParTransferencia($idDestino, $idOrigem);

            $conexao->commit();
        } catch (PDOException $e) {
            $conexao->rollBack();
            error_log('[TransactionService::criarTransferencia] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar a transferência.', 0, $e);
        }

        return $this->obter($idOrigem, $userId);
    }

    private function criarRecorrente(int $userId, array $dados): array
    {
        $recorrenciaTipo = (string) $dados['recorrencia_tipo'];

        if (!Validator::dentroDe($recorrenciaTipo, self::RECORRENCIAS_VALIDAS)) {
            throw new InvalidArgumentException('Tipo de recorrência inválido.');
        }

        $comum = $this->validarComum($userId, $dados);
        $grupoId = $this->gerarUuid();
        $dataBase = new \DateTimeImmutable($comum['data']);

        $intervalo = [
            'semanal' => 'P7D',
            'mensal' => 'P1M',
            'anual' => 'P1Y',
        ][$recorrenciaTipo];

        $primeiroId = null;
        for ($i = 0; $i < self::OCORRENCIAS_RECORRENCIA; $i++) {
            if ($i > 0) {
                $dataBase = $dataBase->add(new \DateInterval($intervalo));
            }
            $dataOcorrencia = $dataBase;

            $linha = $this->paraLinha($comum);
            $linha['data'] = $dataOcorrencia->format('Y-m-d');
            $linha['status'] = $i === 0 ? $comum['status'] : 'pendente';
            $linha['recorrencia_tipo'] = $recorrenciaTipo;
            $linha['recorrencia_grupo_id'] = $grupoId;

            $id = $this->transacoes->criar($userId, $linha);
            if ($primeiroId === null) {
                $primeiroId = $id;
            }
        }

        return $this->obter($primeiroId, $userId);
    }

    private function criarParcelada(int $userId, array $dados): array
    {
        $parcelaTotal = (int) $dados['parcela_total'];

        if ($parcelaTotal < 2 || $parcelaTotal > 60) {
            throw new InvalidArgumentException('Número de parcelas inválido (use entre 2 e 60).');
        }

        $comum = $this->validarComum($userId, $dados);
        $valores = Money::distribuirParcelas($comum['valor'], $parcelaTotal);
        $grupoId = $this->gerarUuid();
        $dataBase = new \DateTimeImmutable($comum['data']);

        $primeiroId = null;
        for ($i = 0; $i < $parcelaTotal; $i++) {
            $dataParcela = $dataBase->add(new \DateInterval('P' . $i . 'M'));

            $linha = $this->paraLinha($comum);
            $linha['valor'] = $valores[$i];
            $linha['data'] = $dataParcela->format('Y-m-d');
            $linha['vencimento'] = $dataParcela->format('Y-m-d');
            $linha['status'] = $i === 0 ? $comum['status'] : 'pendente';
            $linha['parcela_atual'] = $i + 1;
            $linha['parcela_total'] = $parcelaTotal;
            $linha['parcela_grupo_id'] = $grupoId;
            $linha['descricao'] = $comum['descricao'] . ' (' . ($i + 1) . '/' . $parcelaTotal . ')';

            $id = $this->transacoes->criar($userId, $linha);
            if ($primeiroId === null) {
                $primeiroId = $id;
            }
        }

        return $this->obter($primeiroId, $userId);
    }

    private function validarComum(int $userId, array $dados, ?string $tipoAtual = null): array
    {
        $tipo = (string) ($dados['tipo'] ?? $tipoAtual ?? 'despesa');

        if (!Validator::dentroDe($tipo, ['receita', 'despesa'])) {
            throw new InvalidArgumentException('Tipo de transação inválido.');
        }

        $descricao = trim((string) ($dados['descricao'] ?? ''));
        if ($descricao === '') {
            throw new InvalidArgumentException('Descrição é obrigatória.');
        }

        $accountId = !empty($dados['account_id']) ? (int) $dados['account_id'] : 0;
        $this->exigirContaDoUsuario($accountId, $userId);

        $categoryId = !empty($dados['category_id']) ? (int) $dados['category_id'] : null;
        if ($categoryId !== null && $this->categorias->buscarPorIdEUsuario($categoryId, $userId) === null) {
            throw new InvalidArgumentException('Categoria inválida.');
        }

        $valor = Money::normalizarEntrada((string) ($dados['valor'] ?? ''));
        if (!Money::ehPositivo($valor)) {
            throw new InvalidArgumentException('O valor precisa ser positivo.');
        }

        $status = (string) ($dados['status'] ?? 'pendente');
        if (!Validator::dentroDe($status, self::STATUS_VALIDOS)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        return [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'tipo' => $tipo,
            'direcao' => $tipo === 'receita' ? 'entrada' : 'saida',
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $this->validarData($dados['data'] ?? null),
            'vencimento' => !empty($dados['vencimento']) ? $dados['vencimento'] : null,
            'status' => $status,
            'observacao' => !empty($dados['observacao']) ? trim((string) $dados['observacao']) : null,
        ];
    }

    private function paraLinha(array $comum): array
    {
        return array_merge($comum, [
            'recorrencia_tipo' => null,
            'recorrencia_grupo_id' => null,
            'parcela_atual' => null,
            'parcela_total' => null,
            'parcela_grupo_id' => null,
        ]);
    }

    private function paraLinhaAtualizacao(array $comum): array
    {
        return [
            'account_id' => $comum['account_id'],
            'category_id' => $comum['category_id'],
            'tipo' => $comum['tipo'],
            'descricao' => $comum['descricao'],
            'valor' => $comum['valor'],
            'data' => $comum['data'],
            'vencimento' => $comum['vencimento'],
            'status' => $comum['status'],
            'observacao' => $comum['observacao'],
        ];
    }

    private function exigirContaDoUsuario(int $accountId, int $userId): void
    {
        if ($accountId <= 0 || $this->contas->buscarPorIdEUsuario($accountId, $userId) === null) {
            throw new InvalidArgumentException('Conta inválida.');
        }
    }

    private function validarData(?string $data): string
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data é obrigatória.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            throw new InvalidArgumentException('Data inválida.');
        }

        return $data;
    }

    private function gerarUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
