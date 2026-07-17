<?php

namespace Jayi\Toon\Encoding;

/**
 * Encodes numbers to TOON canonical decimal form.
 *
 * No scientific notation, no trailing zeros, no leading zeros.
 * NaN and Infinity return null (encoded as `null` by the caller).
 */
class NumberEncoder
{
    /**
     * Encode a number to canonical decimal string, or null for NaN/Infinity.
     */
    public static function encode(int|float $value): ?string
    {
        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }

            if ($value == 0.0) {
                return '0';
            }

            return self::formatFloat($value);
        }

        return (string) $value;
    }

    private static function formatFloat(float $value): string
    {
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        if ($abs >= 1e21) {
            return $sign.self::formatExponent($abs);
        }

        if ($abs < 1e-6) {
            return $sign.self::formatExponent($abs);
        }

        $formatted = self::shortestRoundtrip($abs);

        if (str_contains($formatted, 'E') || str_contains($formatted, 'e')) {
            return $sign.self::expandScientific($formatted);
        }

        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $sign.$formatted;
    }

    /**
     * Format a float with the fewest significant digits that still parse back
     * to the exact same value, preserving full IEEE-754 precision.
     */
    private static function shortestRoundtrip(float $abs): string
    {
        for ($precision = 1; $precision <= 17; $precision++) {
            $candidate = sprintf('%.'.$precision.'g', $abs);

            if ((float) $candidate === $abs) {
                return $candidate;
            }
        }

        return sprintf('%.17g', $abs);
    }

    private static function formatExponent(float $abs): string
    {
        // §2: outside canonical range, emit JSON-number exponent form (lowercase e, explicit sign)
        $str = sprintf('%.17e', $abs);
        // Normalize: strip trailing zeros in mantissa, use lowercase e+XX format
        preg_match('/^(\d+\.\d+?)0*e([+-]\d+)$/', $str, $m);

        if (empty($m)) {
            return $str;
        }

        $mantissa = rtrim($m[1], '.');
        $exp = (int) $m[2];
        $sign = $exp >= 0 ? '+' : '-';

        return $mantissa.'e'.$sign.abs($exp);
    }

    private static function expandScientific(string $sci): string
    {
        preg_match('/^(-?)(\d+\.?\d*)[eE]([+-]?\d+)$/', $sci, $m);

        if (empty($m)) {
            return $sci;
        }

        $negative = $m[1];
        $digits = str_replace('.', '', $m[2]);
        $exponent = (int) $m[3];
        $dotPos = strpos($m[2], '.');
        $intDigits = $dotPos !== false ? $dotPos : strlen($m[2]);
        $newDotPos = $intDigits + $exponent;

        if ($newDotPos <= 0) {
            $result = '0.'.str_repeat('0', -$newDotPos).$digits;
        } elseif ($newDotPos >= strlen($digits)) {
            $result = $digits.str_repeat('0', $newDotPos - strlen($digits));
        } else {
            $result = substr($digits, 0, $newDotPos).'.'.substr($digits, $newDotPos);
        }

        if (str_contains($result, '.')) {
            $result = rtrim(rtrim($result, '0'), '.');
        }

        return $negative.$result;
    }
}
