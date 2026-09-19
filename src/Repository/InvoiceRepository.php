<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class InvoiceRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function conexao(): PDO
    {
        return $this->connection;
    }

    public function listarPorCartao(int $cardId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM card_invoices WHERE card_id = ? AND user_id = ? ORDER BY mes_referencia DESC'
            );
            $stmt->execute([$cardId, $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::listarPorCartao] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as faturas.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT ci.*, c.nome AS cartao_nome, c.limite AS cartao_limite
                 FROM card_invoices ci
                 JOIN cards c ON c.id = ci.card_id
                 WHERE ci.id = ? AND ci.user_id = ? LIMIT 1'
            );
            $stmt->execute([$id, $userId]);

            $fatura = $stmt->fetch();

            return $fatura ?: null;
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a fatura.', 0, $e);
        }
    }

    /**
     * SELECT ... FOR UPDATE — trava a linha até o commit/rollback da transação
     * corrente, pra dois pagamentos concorrentes da mesma fatura não passarem
     * ambos pela checagem de status antes de qualquer um gravar.
     */
    public function buscarParaAtualizar(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM card_invoices WHERE id = ? AND user_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$id, $userId]);

            $fatura = $stmt->fetch();

            return $fatura ?: null;
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::buscarParaAtualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a fatura.', 0, $e);
        }
    }

    public function buscarPorCartaoEMes(int $cardId, string $mesReferencia): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM card_invoices WHERE card_id = ? AND mes_referencia = ? LIMIT 1'
            );
            $stmt->execute([$cardId, $mesReferencia]);

            $fatura = $stmt->fetch();

            return $fatura ?: null;
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::buscarPorCartaoEMes] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a fatura.', 0, $e);
        }
    }

    public function criarFatura(int $cardId, int $userId, string $mesReferencia, string $dataFechamento, string $dataVencimento): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO card_invoices (card_id, user_id, mes_referencia, data_fechamento, data_vencimento)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$cardId, $userId, $mesReferencia, $dataFechamento, $dataVencimento]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::criarFatura] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a fatura.', 0, $e);
        }
    }

    public function incrementarValorFatura(int $invoiceId, string $valor): void
    {
        try {
            $this->connection->prepare('UPDATE card_invoices SET valor_total = valor_total + ? WHERE id = ?')
                ->execute([$valor, $invoiceId]);
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::incrementarValorFatura] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar o valor da fatura.', 0, $e);
        }
    }

    /**
     * UPDATE condicional (WHERE status <> 'paga') — é a proteção atômica
     * contra pagar a mesma fatura duas vezes (duplo clique/corrida).
     * rowCount() === 0 significa que já estava paga.
     */
    public function marcarComoPaga(int $invoiceId, int $transacaoId): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "UPDATE card_invoices SET status = 'paga', transacao_pagamento_id = ? WHERE id = ? AND status <> 'paga'"
            );
            $stmt->execute([$transacaoId, $invoiceId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::marcarComoPaga] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar o pagamento da fatura.', 0, $e);
        }
    }

    public function criarCompra(int $cardId, int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO card_purchases (card_id, user_id, categoria_id, descricao, valor_total, parcelas_total, data_compra)
                 VALUES (:card_id, :user_id, :categoria_id, :descricao, :valor_total, :parcelas_total, :data_compra)'
            );
            $stmt->execute(array_merge(['card_id' => $cardId, 'user_id' => $userId], $dados));

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::criarCompra] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar a compra.', 0, $e);
        }
    }

    public function criarParcela(int $purchaseId, int $invoiceId, int $numero, string $valor): void
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO card_purchase_installments (card_purchase_id, card_invoice_id, numero_parcela, valor_parcela)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$purchaseId, $invoiceId, $numero, $valor]);
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::criarParcela] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar a parcela.', 0, $e);
        }
    }

    public function listarParcelasPorCompra(int $purchaseId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT pi.*, ci.mes_referencia
                 FROM card_purchase_installments pi
                 JOIN card_purchases p ON p.id = pi.card_purchase_id
                 JOIN card_invoices ci ON ci.id = pi.card_invoice_id
                 WHERE pi.card_purchase_id = ? AND p.user_id = ?
                 ORDER BY pi.numero_parcela ASC'
            );
            $stmt->execute([$purchaseId, $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::listarParcelasPorCompra] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as parcelas.', 0, $e);
        }
    }

    public function listarComprasPorCartao(int $cardId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM card_purchases WHERE card_id = ? AND user_id = ? ORDER BY data_compra DESC'
            );
            $stmt->execute([$cardId, $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::listarComprasPorCartao] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as compras.', 0, $e);
        }
    }

    public function marcarParcelasDaFaturaComoPagas(int $invoiceId): void
    {
        try {
            $this->connection->prepare("UPDATE card_purchase_installments SET status = 'paga' WHERE card_invoice_id = ?")
                ->execute([$invoiceId]);
        } catch (PDOException $e) {
            error_log('[InvoiceRepository::marcarParcelasDaFaturaComoPagas] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar as parcelas da fatura.', 0, $e);
        }
    }
}
