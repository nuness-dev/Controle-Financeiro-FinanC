<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function id(): ?int
    {
        Session::start();

        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function login(int $userId, string $nome, string $perfil = 'usuario', ?string $foto = null): void
    {
        Session::start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_perfil'] = $perfil;
        $_SESSION['user_foto'] = $foto;
    }

    public static function nome(): string
    {
        Session::start();

        return (string) ($_SESSION['user_nome'] ?? '');
    }

    public static function foto(): ?string
    {
        Session::start();

        return $_SESSION['user_foto'] ?? null;
    }

    public static function perfil(): string
    {
        Session::start();

        return (string) ($_SESSION['user_perfil'] ?? 'usuario');
    }

    public static function isAdmin(): bool
    {
        return self::perfil() === 'admin';
    }

    public static function atualizarSessao(string $nome, ?string $foto): void
    {
        Session::start();

        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_foto'] = $foto;
    }

    public static function logout(): void
    {
        Session::start();

        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        $isApiRequest = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($isApiRequest) {
            Response::json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        header('Location: login.php');
        exit;
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (self::isAdmin()) {
            return;
        }

        $isApiRequest = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($isApiRequest) {
            Response::json(['success' => false, 'message' => 'Acesso restrito a administradores.'], 403);
        }

        header('Location: dashboard.php');
        exit;
    }
}
