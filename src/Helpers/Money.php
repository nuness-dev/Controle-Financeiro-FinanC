<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

/**
 * Toda regra de dinheiro do sistema passa por aqui. Trabalha sempre em
 * centavos (inteiro) internamente pra evitar erro de arredondamento, e só
 * converte pra string decimal na borda (banco/JSON).
 */
final class Money
{
    /**
     * Aceita "1.000,50", "1000.50", "R$ 1.000,50" ou "1000,5" e devolve uma
     * string decimal segura ("1000.50") pronta pra ir no banco.
     */
    public static function normalizarEntrada(string $valor): string
    {
        $limpo = trim(str_replace(['R$', ' '], '', $valor));

        if ($limpo === '') {
            throw new InvalidArgumentException('Valor monetário vazio.');
        }

        $temVirgula = strpos($limpo, ',') !== false;
        $temPonto = strpos($limpo, '.') !== false;

        if ($temVirgula && $temPonto) {
            // "1.000,50" -> ponto é separador de milhar, vírgula é decimal
            $limpo = str_replace('.', '', $limpo);
            $limpo = str_replace(',', '.', $limpo);
        } elseif ($temVirgula) {
            // "1000,50" -> vírgula é decimal
            $limpo = str_replace(',', '.', $limpo);
        }
        // "1000.50" já está no formato certo

        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $limpo)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        return self::centavosParaDecimal(self::paraCentavos($limpo));
    }

    public static function paraCentavos(string $valorDecimal): int
    {
        $valorDecimal = trim($valorDecimal);
        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $valorDecimal)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        $negativo = strpos($valorDecimal, '-') === 0;
        $semSinal = ltrim($valorDecimal, '-');
        [$reais, $centavos] = array_pad(explode('.', $semSinal, 2), 2, '0');
        $total = ((int) $reais * 100) + (int) str_pad(substr($centavos, 0, 2), 2, '0');

        return $negativo ? -$total : $total;
    }

    public static function centavosParaDecimal(int $centavos): string
    {
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return sprintf('%s%d.%02d', $sinal, intdiv($absoluto, 100), $absoluto % 100);
    }

    public static function formatarDecimal(float $valor): string
    {
        return number_format($valor, 2, '.', '');
    }

    public static function formatarBRL(string $valorDecimal): string
    {
        $centavos = self::paraCentavos($valorDecimal);
        $sinal = $centavos < 0 ? '-' : '';
        $absoluto = abs($centavos);

        return $sinal . 'R$ ' . number_format($absoluto / 100, 2, ',', '.');
    }

    public static function ehPositivo(string $valorDecimal): bool
    {
        return self::paraCentavos($valorDecimal) > 0;
    }

    /**
     * Distribui um valor total em N parcelas sem perder centavo. O ajuste de
     * centavos fica nas primeiras parcelas, como em 100 / 3 = 33,34 + 33,33 + 33,33.
     *
     * @return string[] lista de valores decimais, uma por parcela
     */
    public static function distribuirParcelas(string $valorTotalDecimal, int $numeroParcelas): array
    {
        if ($numeroParcelas < 1) {
            throw new InvalidArgumentException('Número de parcelas inválido.');
        }

        $totalCentavos = self::paraCentavos($valorTotalDecimal);
        $parcelaCentavos = intdiv($totalCentavos, $numeroParcelas);
        $resto = $totalCentavos - ($parcelaCentavos * $numeroParcelas);

        $parcelas = [];
        for ($i = 0; $i < $numeroParcelas; $i++) {
            $valorDaParcela = $parcelaCentavos;
            if ($i < $resto) {
                $valorDaParcela++;
            }
            $parcelas[] = self::centavosParaDecimal($valorDaParcela);
        }

        return $parcelas;
    }
}
