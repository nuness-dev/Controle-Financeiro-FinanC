<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class AttachmentRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarPorTransacao(int $transactionId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM transaction_attachments WHERE transaction_id = ? AND user_id = ? ORDER BY criado_em DESC'
            );
            $stmt->execute([$transactionId, $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[AttachmentRepository::listarPorTransacao] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar os anexos.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM transaction_attachments WHERE id = ? AND user_id = ? LIMIT 1'
            );
            $stmt->execute([$id, $userId]);

            $anexo = $stmt->fetch();

            return $anexo ?: null;
        } catch (PDOException $e) {
            error_log('[AttachmentRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar o anexo.', 0, $e);
        }
    }

    public function criar(int $transactionId, int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO transaction_attachments (transaction_id, user_id, nome_original, nome_arquivo, mime, tamanho)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $transactionId,
                $userId,
                $dados['nome_original'],
                $dados['nome_arquivo'],
                $dados['mime'],
                $dados['tamanho'],
            ]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[AttachmentRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível salvar o anexo.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM transaction_attachments WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[AttachmentRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir o anexo.', 0, $e);
        }
    }
}
