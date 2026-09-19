<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    public static function emailValido(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function senhaForte(string $senha): bool
    {
        return strlen($senha) >= 8;
    }

    public static function corHexValida(string $cor): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $cor);
    }

    public static function dentroDe(string $valor, array $permitidos): bool
    {
        return in_array($valor, $permitidos, true);
    }
}
