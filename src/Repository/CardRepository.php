<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class CardRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarPorUsuario(int $userId): array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM cards WHERE user_id = ? ORDER BY nome ASC');
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[CardRepository::listarPorUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar os cartões.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM cards WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, $userId]);

            $cartao = $stmt->fetch();

            return $cartao ?: null;
        } catch (PDOException $e) {
            error_log('[CardRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar o cartão.', 0, $e);
        }
    }

    public function criar(int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO cards (user_id, nome, banco, ultimos_digitos, limite, dia_fechamento, dia_vencimento, cor)
                 VALUES (:user_id, :nome, :banco, :ultimos_digitos, :limite, :dia_fechamento, :dia_vencimento, :cor)'
            );
            $stmt->execute(array_merge(['user_id' => $userId], $dados));

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[CardRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar o cartão.', 0, $e);
        }
    }

    public function atualizar(int $id, int $userId, array $dados): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'UPDATE cards SET nome = :nome, banco = :banco, ultimos_digitos = :ultimos_digitos,
                    limite = :limite, dia_fechamento = :dia_fechamento, dia_vencimento = :dia_vencimento,
                    cor = :cor, ativo = :ativo
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute(array_merge($dados, ['id' => $id, 'user_id' => $userId]));

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[CardRepository::atualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar o cartão.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM cards WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[CardRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir o cartão.', 0, $e);
        }
    }
}
