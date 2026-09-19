<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class PasswordResetRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function criar(int $userId, string $tokenHash, string $expiraEm): void
    {
        try {
            $this->connection->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);

            $stmt = $this->connection->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expira_em) VALUES (?, ?, ?)'
            );
            $stmt->execute([$userId, $tokenHash, $expiraEm]);
        } catch (PDOException $e) {
            error_log('[PasswordResetRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível gerar o link de redefinição.', 0, $e);
        }
    }

    public function buscarValidoPorTokenHash(string $tokenHash): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM password_resets WHERE token_hash = ? AND expira_em > NOW() LIMIT 1'
            );
            $stmt->execute([$tokenHash]);

            $registro = $stmt->fetch();

            return $registro ?: null;
        } catch (PDOException $e) {
            error_log('[PasswordResetRepository::buscarValidoPorTokenHash] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível validar o link de redefinição.', 0, $e);
        }
    }

    public function excluirPorUsuario(int $userId): void
    {
        try {
            $this->connection->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);
        } catch (PDOException $e) {
            error_log('[PasswordResetRepository::excluirPorUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível concluir a redefinição de senha.', 0, $e);
        }
    }
}
