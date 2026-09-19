<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;

final class UserRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            $usuario = $stmt->fetch();

            return $usuario ?: null;
        } catch (PDOException $e) {
            error_log('[UserRepository::buscarPorEmail] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar o usuário.', 0, $e);
        }
    }

    public function buscarPorId(int $id): ?array
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);

            $usuario = $stmt->fetch();

            return $usuario ?: null;
        } catch (PDOException $e) {
            error_log('[UserRepository::buscarPorId] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível consultar o usuário.', 0, $e);
        }
    }

    public function criar(string $nome, string $email, string $senhaHash): int
    {
        try {
            $stmt = $this->connection->prepare(
                'INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$nome, $email, $senhaHash]);

            return (int) $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log('[UserRepository::criar] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível criar a conta.', 0, $e);
        }
    }

    public function atualizarPerfil(int $id, string $nome, string $email): void
    {
        try {
            $stmt = $this->connection->prepare('UPDATE users SET nome = ?, email = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::atualizarPerfil] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar o perfil.', 0, $e);
        }
    }

    public function atualizarFoto(int $id, ?string $foto): void
    {
        try {
            $stmt = $this->connection->prepare('UPDATE users SET foto = ? WHERE id = ?');
            $stmt->execute([$foto, $id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::atualizarFoto] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a foto.', 0, $e);
        }
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        try {
            $stmt = $this->connection->prepare('UPDATE users SET senha_hash = ? WHERE id = ?');
            $stmt->execute([$senhaHash, $id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::atualizarSenha] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível atualizar a senha.', 0, $e);
        }
    }

    public function registrarAcesso(int $id): void
    {
        try {
            $this->connection->prepare('UPDATE users SET ultimo_acesso = NOW() WHERE id = ?')->execute([$id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::registrarAcesso] ' . $e->getMessage());
        }
    }

    public function listarTodos(): array
    {
        try {
            return $this->connection->query(
                'SELECT id, nome, email, perfil, ativo, ultimo_acesso, criado_em FROM users ORDER BY criado_em DESC'
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('[UserRepository::listarTodos] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível carregar os usuários.', 0, $e);
        }
    }

    public function contarAtivos(): int
    {
        try {
            return (int) $this->connection->query('SELECT COUNT(*) FROM users WHERE ativo = 1')->fetchColumn();
        } catch (PDOException $e) {
            error_log('[UserRepository::contarAtivos] ' . $e->getMessage());

            return 0;
        }
    }

    public function alterarStatus(int $id, bool $ativo): void
    {
        try {
            $this->connection->prepare('UPDATE users SET ativo = ? WHERE id = ?')->execute([$ativo ? 1 : 0, $id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::alterarStatus] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível alterar o status do usuário.', 0, $e);
        }
    }

    public function alterarPerfil(int $id, string $perfil): void
    {
        try {
            $this->connection->prepare('UPDATE users SET perfil = ? WHERE id = ?')->execute([$perfil, $id]);
        } catch (PDOException $e) {
            error_log('[UserRepository::alterarPerfil] ' . $e->getMessage());

            throw new RuntimeException('Não foi possível alterar o perfil do usuário.', 0, $e);
        }
    }
}
