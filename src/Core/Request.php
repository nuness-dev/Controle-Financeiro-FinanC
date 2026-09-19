<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private $data;

    public function __construct()
    {
        $body = file_get_contents('php://input');
        $json = json_decode($body, true);

        $this->data = is_array($json) ? array_merge($_POST, $json) : $_POST;
    }

    /**
     * @param mixed $padrao
     * @return mixed
     */
    public function input(string $chave, $padrao = null)
    {
        return $this->data[$chave] ?? $padrao;
    }

    /**
     * @param mixed $padrao
     * @return mixed
     */
    public function query(string $chave, $padrao = null)
    {
        return $_GET[$chave] ?? $padrao;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}
