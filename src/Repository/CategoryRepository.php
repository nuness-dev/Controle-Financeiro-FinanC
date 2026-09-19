<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class CategoryRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarPorUsuario(int $userId, ?string $tipo = null): array
    {
        try {
            $sql = 'SELECT * FROM categories WHERE user_id = ?';
            $params = [$userId];

            if ($tipo !== null) {
                $sql .= ' AND tipo = ?';
                $params[] = $tipo;
            }

            $sql .= ' ORDER BY nome ASC';

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[CategoryRepository::listarPorUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as categorias.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM categories WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, $userId]);

            $categoria = $stmt->fetch();

            return $categoria ?: null;
        } catch (PDOException $e) {
            error_log('[CategoryRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a categoria.', 0, $e);
        }
    }

    public function criar(int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO categories (user_id, nome, tipo, cor, icone) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $dados['nome'], $dados['tipo'], $dados['cor'], $dados['icone']]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[CategoryRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a categoria.', 0, $e);
        }
    }

    public function atualizar(int $id, int $userId, array $dados): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'UPDATE categories SET nome = ?, tipo = ?, cor = ?, icone = ?, ativa = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$dados['nome'], $dados['tipo'], $dados['cor'], $dados['icone'], $dados['ativa'], $id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[CategoryRepository::atualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a categoria.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[CategoryRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir a categoria.', 0, $e);
        }
    }
}
