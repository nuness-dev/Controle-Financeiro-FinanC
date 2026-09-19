<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Helpers\Money;
use PDO;
use PDOException;
use RuntimeException;

final class AccountRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarPorUsuario(int $userId): array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM accounts WHERE user_id = ? ORDER BY nome ASC');
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[AccountRepository::listarPorUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as contas.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM accounts WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, $userId]);

            $conta = $stmt->fetch();

            return $conta ?: null;
        } catch (PDOException $e) {
            error_log('[AccountRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a conta.', 0, $e);
        }
    }

    public function criar(int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO accounts (user_id, nome, tipo, saldo_inicial, instituicao, cor)
                 VALUES (:user_id, :nome, :tipo, :saldo_inicial, :instituicao, :cor)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'nome' => $dados['nome'],
                'tipo' => $dados['tipo'],
                'saldo_inicial' => $dados['saldo_inicial'],
                'instituicao' => $dados['instituicao'],
                'cor' => $dados['cor'],
            ]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[AccountRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a conta.', 0, $e);
        }
    }

    public function atualizar(int $id, int $userId, array $dados): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'UPDATE accounts SET nome = :nome, tipo = :tipo, saldo_inicial = :saldo_inicial,
                    instituicao = :instituicao, cor = :cor, ativa = :ativa
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'nome' => $dados['nome'],
                'tipo' => $dados['tipo'],
                'saldo_inicial' => $dados['saldo_inicial'],
                'instituicao' => $dados['instituicao'],
                'cor' => $dados['cor'],
                'ativa' => $dados['ativa'],
                'id' => $id,
                'user_id' => $userId,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[AccountRepository::atualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a conta.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM accounts WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[AccountRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir a conta.', 0, $e);
        }
    }

    public function temMovimentacao(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT COUNT(*) FROM transactions WHERE account_id = ? AND user_id = ?'
            );
            $stmt->execute([$id, $userId]);

            return ((int) $stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log('[AccountRepository::temMovimentacao] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível verificar movimentações da conta.', 0, $e);
        }
    }

    /**
     * Fonte única de cálculo de saldo. `direcao` já carrega o sinal —
     * entrada soma, saída subtrai — então a mesma query serve pra
     * "atual" (só pago) e "previsto" (pago + pendente).
     *
     * @return array{atual: string, previsto: string}
     */
    public function calcularSaldo(int $contaId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT saldo_inicial FROM accounts WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$contaId, $userId]);
            $conta = $stmt->fetch();

            if (!$conta) {
                return ['atual' => '0.00', 'previsto' => '0.00'];
            }

            $movStmt = $this->connection->prepare(
                "SELECT status, SUM(CASE WHEN direcao = 'entrada' THEN valor ELSE -valor END) AS total
                 FROM transactions
                 WHERE account_id = ? AND user_id = ? AND status IN ('pago', 'pendente')
                 GROUP BY status"
            );
            $movStmt->execute([$contaId, $userId]);

            $pago = 0;
            $pendente = 0;
            foreach ($movStmt->fetchAll() as $linha) {
                if ($linha['status'] === 'pago') {
                    $pago = Money::paraCentavos((string) $linha['total']);
                } elseif ($linha['status'] === 'pendente') {
                    $pendente = Money::paraCentavos((string) $linha['total']);
                }
            }

            $saldoInicial = Money::paraCentavos((string) $conta['saldo_inicial']);
            $atual = $saldoInicial + $pago;
            $previsto = $atual + $pendente;

            return [
                'atual' => Money::centavosParaDecimal($atual),
                'previsto' => Money::centavosParaDecimal($previsto),
            ];
        } catch (PDOException $e) {
            error_log('[AccountRepository::calcularSaldo] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível calcular o saldo.', 0, $e);
        }
    }

    /**
     * Soma o saldo atual/previsto de todas as contas ativas do usuário —
     * usada pelo dashboard e relatórios (reaproveita calcularSaldo por conta).
     */
    public function calcularSaldoTotal(int $userId): array
    {
        $contas = $this->listarPorUsuario($userId);

        $atual = 0;
        $previsto = 0;

        foreach ($contas as $conta) {
            if (!$conta['ativa']) {
                continue;
            }

            $saldo = $this->calcularSaldo((int) $conta['id'], $userId);
            $atual += Money::paraCentavos($saldo['atual']);
            $previsto += Money::paraCentavos($saldo['previsto']);
        }

        return [
            'atual' => Money::centavosParaDecimal($atual),
            'previsto' => Money::centavosParaDecimal($previsto),
        ];
    }
}
