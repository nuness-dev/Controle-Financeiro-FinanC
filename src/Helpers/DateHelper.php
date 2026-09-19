<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateHelper
{
    public static function paraIso(string $dataBr): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataBr)) {
            return $dataBr;
        }

        if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataBr, $m)) {
            throw new InvalidArgumentException('Data inválida.');
        }

        return sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);
    }

    public static function paraBr(?string $dataIso): ?string
    {
        if (empty($dataIso)) {
            return null;
        }

        $partes = explode('-', substr($dataIso, 0, 10));
        if (count($partes) !== 3) {
            return null;
        }

        return sprintf('%s/%s/%s', $partes[2], $partes[1], $partes[0]);
    }

    /**
     * Último dia válido de um mês/ano pra um "dia" desejado — evita
     * "31 de fevereiro" ao calcular fechamento/vencimento de cartão.
     */
    public static function diaSeguro(int $ano, int $mes, int $dia): int
    {
        $ultimoDiaDoMes = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->format('t');

        return min($dia, $ultimoDiaDoMes);
    }
}
