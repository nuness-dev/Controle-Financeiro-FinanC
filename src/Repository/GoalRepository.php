<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class GoalRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function listarPorUsuario(int $userId): array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM goals WHERE user_id = ? ORDER BY criado_em DESC');
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[GoalRepository::listarPorUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as metas.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM goals WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, $userId]);

            $meta = $stmt->fetch();

            return $meta ?: null;
        } catch (PDOException $e) {
            error_log('[GoalRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a meta.', 0, $e);
        }
    }

    public function criar(int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO goals (user_id, titulo, valor_objetivo, prazo, cor) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $dados['titulo'], $dados['valor_objetivo'], $dados['prazo'], $dados['cor']]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[GoalRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a meta.', 0, $e);
        }
    }

    public function atualizar(int $id, int $userId, array $dados): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'UPDATE goals SET titulo = ?, valor_objetivo = ?, prazo = ?, cor = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$dados['titulo'], $dados['valor_objetivo'], $dados['prazo'], $dados['cor'], $id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[GoalRepository::atualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a meta.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM goals WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[GoalRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir a meta.', 0, $e);
        }
    }

    public function adicionarContribuicao(int $goalId, int $userId, string $valor, ?string $observacao): void
    {
        try {
            $this->connection->beginTransaction();

            $stmt = $this->connection->prepare(
                'INSERT INTO goal_contributions (goal_id, user_id, valor, observacao) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$goalId, $userId, $valor, $observacao]);

            $this->connection->prepare('UPDATE goals SET valor_atual = valor_atual + ? WHERE id = ? AND user_id = ?')
                ->execute([$valor, $goalId, $userId]);

            $this->connection->commit();
        } catch (PDOException $e) {
            $this->connection->rollBack();
            error_log('[GoalRepository::adicionarContribuicao] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível registrar a contribuição.', 0, $e);
        }
    }

    public function listarContribuicoes(int $goalId, int $userId): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT * FROM goal_contributions WHERE goal_id = ? AND user_id = ? ORDER BY criado_em DESC'
            );
            $stmt->execute([$goalId, $userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[GoalRepository::listarContribuicoes] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar o histórico da meta.', 0, $e);
        }
    }
}
