<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class TransactionRepository
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

    public function listarPaginado(int $userId, array $filtros, array $ordenacao, int $pagina, int $porPagina): array
    {
        [$where, $params] = $this->montarFiltros($userId, $filtros);

        try {
            $stmtTotal = $this->connection->prepare("SELECT COUNT(*) FROM transactions t $where");
            $stmtTotal->execute($params);
            $total = (int) $stmtTotal->fetchColumn();

            $offset = ($pagina - 1) * $porPagina;
            $ordem = sprintf('t.%s %s', $ordenacao['coluna'], $ordenacao['direcao']);

            $stmt = $this->connection->prepare(
                "SELECT t.*, c.nome AS categoria_nome, c.cor AS categoria_cor, c.icone AS categoria_icone,
                        a.nome AS conta_nome, a.cor AS conta_cor
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 LEFT JOIN accounts a ON a.id = t.account_id
                 $where
                 ORDER BY $ordem
                 LIMIT :limite OFFSET :offset"
            );

            foreach ($params as $chave => $valor) {
                $stmt->bindValue($chave, $valor);
            }
            $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'dados' => $stmt->fetchAll(),
                'total' => $total,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'totalPaginas' => $porPagina > 0 ? (int) ceil($total / $porPagina) : 0,
            ];
        } catch (PDOException $e) {
            error_log('[TransactionRepository::listarPaginado] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as transações.', 0, $e);
        }
    }

    public function buscarPorIdEUsuario(int $id, int $userId): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT t.*, c.nome AS categoria_nome, c.cor AS categoria_cor, a.nome AS conta_nome
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 LEFT JOIN accounts a ON a.id = t.account_id
                 WHERE t.id = ? AND t.user_id = ? LIMIT 1'
            );
            $stmt->execute([$id, $userId]);

            $tarefa = $stmt->fetch();

            return $tarefa ?: null;
        } catch (PDOException $e) {
            error_log('[TransactionRepository::buscarPorIdEUsuario] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar a transação.', 0, $e);
        }
    }

    public function criar(int $userId, array $dados): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO transactions
                    (user_id, account_id, category_id, tipo, direcao, descricao, valor, data, vencimento, status,
                     observacao, recorrencia_tipo, recorrencia_grupo_id, parcela_atual, parcela_total, parcela_grupo_id)
                 VALUES
                    (:user_id, :account_id, :category_id, :tipo, :direcao, :descricao, :valor, :data, :vencimento, :status,
                     :observacao, :recorrencia_tipo, :recorrencia_grupo_id, :parcela_atual, :parcela_total, :parcela_grupo_id)'
            );
            $stmt->execute(array_merge(['user_id' => $userId], $dados));

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a transação.', 0, $e);
        }
    }

    public function atualizar(int $id, int $userId, array $dados): bool
    {
        try {
            $stmt = $this->connection->prepare(
                'UPDATE transactions SET
                    account_id = :account_id, category_id = :category_id, tipo = :tipo, descricao = :descricao,
                    valor = :valor, data = :data, vencimento = :vencimento, status = :status, observacao = :observacao
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute(array_merge($dados, ['id' => $id, 'user_id' => $userId]));

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[TransactionRepository::atualizar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a transação.', 0, $e);
        }
    }

    public function definirParTransferencia(int $id, int $parId): void
    {
        try {
            $this->connection->prepare('UPDATE transactions SET transferencia_par_id = ? WHERE id = ?')
                ->execute([$parId, $id]);
        } catch (PDOException $e) {
            error_log('[TransactionRepository::definirParTransferencia] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível vincular a transferência.', 0, $e);
        }
    }

    public function excluir(int $id, int $userId): bool
    {
        try {
            $stmt = $this->connection->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[TransactionRepository::excluir] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível excluir a transação.', 0, $e);
        }
    }

    public function contarPorConta(int $accountId, int $userId): int
    {
        try {
            $stmt = $this->connection->prepare('SELECT COUNT(*) FROM transactions WHERE account_id = ? AND user_id = ?');
            $stmt->execute([$accountId, $userId]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::contarPorConta] ' . $e->getMessage());

            return 0;
        }
    }

    public function resumoPeriodo(int $userId, string $dataInicio, string $dataFim): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT tipo, SUM(valor) AS total
                 FROM transactions
                 WHERE user_id = ? AND status = 'pago' AND tipo IN ('receita', 'despesa')
                   AND data BETWEEN ? AND ?
                 GROUP BY tipo"
            );
            $stmt->execute([$userId, $dataInicio, $dataFim]);

            $resultado = ['receita' => '0.00', 'despesa' => '0.00'];
            foreach ($stmt->fetchAll() as $linha) {
                $resultado[$linha['tipo']] = number_format((float) $linha['total'], 2, '.', '');
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log('[TransactionRepository::resumoPeriodo] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível calcular o resumo do período.', 0, $e);
        }
    }

    public function gastosPorCategoria(int $userId, string $dataInicio, string $dataFim): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT COALESCE(c.nome, 'Sem categoria') AS categoria, COALESCE(c.cor, '#94a3b8') AS cor, SUM(t.valor) AS total
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 WHERE t.user_id = ? AND t.status = 'pago' AND t.tipo = 'despesa'
                   AND t.data BETWEEN ? AND ?
                 GROUP BY c.id, c.nome, c.cor
                 ORDER BY total DESC"
            );
            $stmt->execute([$userId, $dataInicio, $dataFim]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::gastosPorCategoria] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível calcular gastos por categoria.', 0, $e);
        }
    }

    public function evolucaoDiaria(int $userId, string $dataInicio, string $dataFim): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT data,
                        SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END) AS receitas,
                        SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END) AS despesas
                 FROM transactions
                 WHERE user_id = ? AND data BETWEEN ? AND ?
                 GROUP BY data
                 ORDER BY data ASC"
            );
            $stmt->execute([$userId, $dataInicio, $dataFim]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::evolucaoDiaria] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível calcular a evolução.', 0, $e);
        }
    }

    public function proximosVencimentos(int $userId, int $limite = 5): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT t.*, c.nome AS categoria_nome
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 WHERE t.user_id = ? AND t.status = 'pendente' AND t.vencimento IS NOT NULL
                 ORDER BY t.vencimento ASC
                 LIMIT ?"
            );
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::proximosVencimentos] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar os próximos vencimentos.', 0, $e);
        }
    }

    public function ultimas(int $userId, int $limite = 5): array
    {
        try {
            $stmt = $this->connection->prepare(
                'SELECT t.*, c.nome AS categoria_nome, c.icone AS categoria_icone
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 WHERE t.user_id = ?
                 ORDER BY t.criado_em DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[TransactionRepository::ultimas] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar as últimas transações.', 0, $e);
        }
    }

    private function montarFiltros(int $userId, array $filtros): array
    {
        $where = 'WHERE t.user_id = :user_id';
        $params = [':user_id' => $userId];

        if (!empty($filtros['search'])) {
            $where .= ' AND t.descricao LIKE :search';
            $params[':search'] = '%' . $filtros['search'] . '%';
        }
        if (!empty($filtros['tipo'])) {
            $where .= ' AND t.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['status'])) {
            $where .= ' AND t.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['category_id'])) {
            $where .= ' AND t.category_id = :category_id';
            $params[':category_id'] = $filtros['category_id'];
        }
        if (!empty($filtros['account_id'])) {
            $where .= ' AND t.account_id = :account_id';
            $params[':account_id'] = $filtros['account_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= ' AND t.data >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where .= ' AND t.data <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'];
        }
        if (isset($filtros['valor_min']) && $filtros['valor_min'] !== null) {
            $where .= ' AND t.valor >= :valor_min';
            $params[':valor_min'] = $filtros['valor_min'];
        }
        if (isset($filtros['valor_max']) && $filtros['valor_max'] !== null) {
            $where .= ' AND t.valor <= :valor_max';
            $params[':valor_max'] = $filtros['valor_max'];
        }

        return [$where, $params];
    }
}
