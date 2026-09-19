<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::loadEnvironment();

        if (!isset($_ENV['DB_HOST'], $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'])) {
            throw new RuntimeException('Configuração do banco ausente. Copie .env.example para .env e preencha os valores.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_DATABASE']
        );

        try {
            self::$instance = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('[Database] Falha na conexão: ' . $e->getMessage());

            throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $e);
        }

        return self::$instance;
    }

    private static function loadEnvironment(): void
    {
        if (isset($_ENV['DB_HOST'])) {
            return;
        }

        $root = dirname(__DIR__, 2);

        if (!file_exists($root . '/.env')) {
            return;
        }

        Dotenv::createImmutable($root)->load();
    }
}
