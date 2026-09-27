<?php

namespace App\Support;

/**
 * Minimal Spanish number-to-words helper used for the mandatory SUNAT amount
 * legend ("SON ... CON xx/100 SOLES").
 */
final class NumberToWords
{
    /** @var array<int, string> */
    private const UNITS = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE',
        'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDOS', 'VEINTITRES',
        'VEINTICUATRO', 'VEINTICINCO', 'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];

    /** @var array<int, string> */
    private const TENS = [
        '', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    /** @var array<int, string> */
    private const HUNDREDS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public static function soles(float $amount): string
    {
        [$whole, $cents] = self::split($amount);

        return sprintf('SON %s CON %02d/100 SOLES', self::integer($whole), $cents);
    }

    /**
     * @return array{0: int, 1: int} whole soles and cents
     */
    public static function split(float $amount): array
    {
        $whole = (int) floor($amount);
        $cents = (int) round(($amount - $whole) * 100);

        if ($cents >= 100) {
            $cents -= 100;
            $whole++;
        }

        return [$whole, $cents];
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return 'CERO';
        }

        if ($number < 0 || $number > 999999999) {
            return (string) $number;
        }

        if ($number < 1000) {
            return self::threeDigits($number);
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $rest = $number % 1000;
            $prefix = $thousands === 1
                ? 'MIL'
                : self::multiplier(self::threeDigits($thousands)).' MIL';

            return $rest > 0 ? $prefix.' '.self::threeDigits($rest) : $prefix;
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;
        $prefix = $millions === 1
            ? 'UN MILLON'
            : self::multiplier(self::threeDigits($millions)).' MILLONES';

        return $rest > 0 ? $prefix.' '.self::integer($rest) : $prefix;
    }

    private static function threeDigits(int $number): string
    {
        if ($number === 100) {
            return 'CIEN';
        }

        $hundreds = intdiv($number, 100);
        $rest = $number % 100;
        $parts = array_filter([self::HUNDREDS[$hundreds]], fn (string $part): bool => $part !== '');

        if ($rest > 0) {
            $parts[] = $rest < 30
                ? self::UNITS[$rest]
                : self::TENS[intdiv($rest, 10)].($rest % 10 > 0 ? ' Y '.self::UNITS[$rest % 10] : '');
        }

        return implode(' ', $parts);
    }

    /**
     * "UNO" becomes "UN" when it multiplies (veintiún mil, treinta y un mil...).
     */
    private static function multiplier(string $words): string
    {
        if (str_ends_with($words, 'UNO')) {
            return substr($words, 0, -1);
        }

        return $words;
    }
}
