<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\DateHelper;
use App\Helpers\Money;
use App\Repository\AccountRepository;
use App\Repository\CardRepository;
use App\Repository\CategoryRepository;
use App\Repository\InvoiceRepository;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

final class InvoiceService
{
    private InvoiceRepository $faturas;
    private CardRepository $cartoes;
    private CategoryRepository $categorias;
    private AccountRepository $contas;
    private TransactionRepository $transacoes;

    public function __construct(
        ?InvoiceRepository $faturas = null,
        ?CardRepository $cartoes = null,
        ?CategoryRepository $categorias = null,
        ?AccountRepository $contas = null,
        ?TransactionRepository $transacoes = null
    ) {
        $this->faturas = $faturas ?? new InvoiceRepository();
        $this->cartoes = $cartoes ?? new CardRepository();
        $this->categorias = $categorias ?? new CategoryRepository();
        $this->contas = $contas ?? new AccountRepository();
        $this->transacoes = $transacoes ?? new TransactionRepository();
    }

    public function listarPorCartao(int $cardId, int $userId): array
    {
        $this->exigirCartaoDoUsuario($cardId, $userId);

        return $this->faturas->listarPorCartao($cardId, $userId);
    }

    public function obter(int $id, int $userId): array
    {
        $fatura = $this->faturas->buscarPorIdEUsuario($id, $userId);

        if ($fatura === null) {
            throw new InvalidArgumentException('Fatura não encontrada.', 404);
        }

        return $fatura;
    }

    /**
     * Única função que decide em qual fatura uma data cai, a partir do dia
     * de fechamento do cartão. Cria a fatura no banco se ainda não existir.
     * Nenhum outro lugar do código reimplementa essa conta.
     */
    public function determinarFatura(int $cardId, int $userId, string $dataReferenciaIso): array
    {
        $cartao = $this->exigirCartaoDoUsuario($cardId, $userId);
        $data = new DateTimeImmutable($dataReferenciaIso);

        $ano = (int) $data->format('Y');
        $mes = (int) $data->format('n');
        $diaFechamento = DateHelper::diaSeguro($ano, $mes, (int) $cartao['dia_fechamento']);

        if ((int) $data->format('j') > $diaFechamento) {
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $ano++;
            }
        }

        $mesReferencia = sprintf('%04d-%02d', $ano, $mes);

        $existente = $this->faturas->buscarPorCartaoEMes($cardId, $mesReferencia);
        if ($existente !== null) {
            return $existente;
        }

        $diaFechamentoFatura = DateHelper::diaSeguro($ano, $mes, (int) $cartao['dia_fechamento']);
        $diaVencimentoFatura = DateHelper::diaSeguro($ano, $mes, (int) $cartao['dia_vencimento']);

        $dataFechamento = sprintf('%04d-%02d-%02d', $ano, $mes, $diaFechamentoFatura);
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $diaVencimentoFatura);

        $id = $this->faturas->criarFatura($cardId, $userId, $mesReferencia, $dataFechamento, $dataVencimento);

        return $this->faturas->buscarPorIdEUsuario($id, $userId);
    }

    public function registrarCompra(int $userId, int $cardId, array $dados): array
    {
        $this->exigirCartaoDoUsuario($cardId, $userId);

        $descricao = trim((string) ($dados['descricao'] ?? ''));
        if ($descricao === '') {
            throw new InvalidArgumentException('Descrição da compra é obrigatória.');
        }

        $categoriaId = !empty($dados['categoria_id']) ? (int) $dados['categoria_id'] : null;
        if ($categoriaId !== null && $this->categorias->buscarPorIdEUsuario($categoriaId, $userId) === null) {
            throw new InvalidArgumentException('Categoria inválida.');
        }

        $valorTotal = Money::normalizarEntrada((string) ($dados['valor_total'] ?? ''));
        if (!Money::ehPositivo($valorTotal)) {
            throw new InvalidArgumentException('O valor da compra precisa ser positivo.');
        }

        $parcelasTotal = max(1, (int) ($dados['parcelas_total'] ?? 1));
        if ($parcelasTotal > 24) {
            throw new InvalidArgumentException('Máximo de 24 parcelas por compra.');
        }

        if (empty($dados['data_compra']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_compra'])) {
            throw new InvalidArgumentException('Data da compra inválida.');
        }

        $valoresParcela = Money::distribuirParcelas($valorTotal, $parcelasTotal);
        $dataCompra = new DateTimeImmutable($dados['data_compra']);

        $conexao = $this->faturas->conexao();

        try {
            $conexao->beginTransaction();

            $purchaseId = $this->faturas->criarCompra($cardId, $userId, [
                'categoria_id' => $categoriaId,
                'descricao' => $descricao,
                'valor_total' => $valorTotal,
                'parcelas_total' => $parcelasTotal,
                'data_compra' => $dados['data_compra'],
            ]);

            for ($i = 0; $i < $parcelasTotal; $i++) {
                $dataParcela = $dataCompra->modify("+{$i} months");
                $fatura = $this->determinarFatura($cardId, $userId, $dataParcela->format('Y-m-d'));

                $this->faturas->criarParcela($purchaseId, (int) $fatura['id'], $i + 1, $valoresParcela[$i]);
                $this->faturas->incrementarValorFatura((int) $fatura['id'], $valoresParcela[$i]);
            }

            $conexao->commit();
        } catch (PDOException $e) {
            $conexao->rollBack();
            error_log('[InvoiceService::registrarCompra] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar a compra.', 0, $e);
        }

        return ['id' => $purchaseId];
    }

    public function listarComprasPorCartao(int $cardId, int $userId): array
    {
        $this->exigirCartaoDoUsuario($cardId, $userId);

        return $this->faturas->listarComprasPorCartao($cardId, $userId);
    }

    public function listarParcelas(int $purchaseId, int $userId): array
    {
        return $this->faturas->listarParcelasPorCompra($purchaseId, $userId);
    }

    /**
     * Fluxo idempotente: trava a linha da fatura (SELECT ... FOR UPDATE),
     * confere se já não foi paga, só então cria a transação de pagamento
     * e atualiza fatura + parcelas — tudo dentro da mesma transação de banco.
     */
    public function pagarFatura(int $invoiceId, int $userId, int $contaId): array
    {
        $this->contas->buscarPorIdEUsuario($contaId, $userId) ?? $this->lancarContaInvalida();

        $conexao = $this->faturas->conexao();

        try {
            $conexao->beginTransaction();

            $fatura = $this->faturas->buscarParaAtualizar($invoiceId, $userId);

            if ($fatura === null) {
                $conexao->rollBack();
                throw new InvalidArgumentException('Fatura não encontrada.', 404);
            }

            if ($fatura['status'] === 'paga') {
                $conexao->rollBack();
                throw new InvalidArgumentException('Esta fatura já foi paga.');
            }

            $cartao = $this->cartoes->buscarPorIdEUsuario((int) $fatura['card_id'], $userId);

            $transacaoId = $this->transacoes->criar($userId, [
                'account_id' => $contaId,
                'category_id' => null,
                'tipo' => 'despesa',
                'direcao' => 'saida',
                'descricao' => 'Pagamento fatura ' . ($cartao['nome'] ?? 'cartão') . ' — ' . $fatura['mes_referencia'],
                'valor' => $fatura['valor_total'],
                'data' => date('Y-m-d'),
                'vencimento' => null,
                'status' => 'pago',
                'observacao' => null,
                'recorrencia_tipo' => null,
                'recorrencia_grupo_id' => null,
                'parcela_atual' => null,
                'parcela_total' => null,
                'parcela_grupo_id' => null,
            ]);

            if (!$this->faturas->marcarComoPaga($invoiceId, $transacaoId)) {
                // Corrida rara: outra requisição pagou entre o SELECT FOR UPDATE e aqui.
                $conexao->rollBack();
                throw new InvalidArgumentException('Esta fatura já foi paga.');
            }

            $this->faturas->marcarParcelasDaFaturaComoPagas($invoiceId);

            $conexao->commit();
        } catch (InvalidArgumentException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            $conexao->rollBack();
            error_log('[InvoiceService::pagarFatura] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar o pagamento da fatura.', 0, $e);
        }

        return $this->obter($invoiceId, $userId);
    }

    private function exigirCartaoDoUsuario(int $cardId, int $userId): array
    {
        $cartao = $this->cartoes->buscarPorIdEUsuario($cardId, $userId);

        if ($cartao === null) {
            throw new InvalidArgumentException('Cartão não encontrado.', 404);
        }

        return $cartao;
    }

    private function lancarContaInvalida(): void
    {
        throw new InvalidArgumentException('Conta inválida.');
    }
}
