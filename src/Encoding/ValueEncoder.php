<?php

namespace Jayi\Toon\Encoding;

use Jayi\Toon\Enums\Delimiter;

/**
 * Encodes individual primitive values to TOON format.
 *
 * Handles quoting decisions per TOON spec section 7.2 and escaping per section 7.1.
 * Only five escape sequences are valid: \\, \", \n, \r, \t.
 */
class ValueEncoder
{
    /**
     * Encode a primitive value, applying quoting and escaping as needed.
     */
    public static function encode(mixed $value, Delimiter $activeDelimiter, Delimiter $documentDelimiter, bool $isArrayContext = false): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            $encoded = NumberEncoder::encode($value);

            return $encoded ?? 'null';
        }

        $str = (string) $value;
        $delimiter = $isArrayContext ? $activeDelimiter : $documentDelimiter;

        if (self::needsQuoting($str, $delimiter)) {
            return self::quote($str);
        }

        return $str;
    }

    /**
     * Encode an object key, quoting only when it doesn't match the identifier pattern.
     */
    public static function encodeKey(string $key): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $key)) {
            return $key;
        }

        return self::quote($key);
    }

    public static function needsQuoting(string $value, Delimiter $delimiter): bool
    {
        if ($value === '') {
            return true;
        }

        if (preg_match('/^\s|\s$/', $value)) {
            return true;
        }

        if (in_array($value, ['true', 'false', 'null'], true)) {
            return true;
        }

        if (preg_match('/^-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?$/', $value)) {
            return true;
        }

        if (preg_match('/^0\d+$/', $value)) {
            return true;
        }

        if (preg_match('/[:\\\\"\\[\\]{}]/', $value)) {
            return true;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return true;
        }

        if (str_contains($value, $delimiter->value)) {
            return true;
        }

        if ($value === '-' || str_starts_with($value, '-')) {
            return true;
        }

        return false;
    }

    public static function quote(string $value): string
    {
        $result = '';
        $len = strlen($value);

        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            $ord = ord($ch);

            $result .= match (true) {
                $ch === '\\' => '\\\\',
                $ch === '"' => '\\"',
                $ch === "\n" => '\\n',
                $ch === "\r" => '\\r',
                $ch === "\t" => '\\t',
                $ord <= 0x1F || $ord === 0x7F => sprintf('\\u%04x', $ord),
                default => $ch,
            };
        }

        return '"'.$result.'"';
    }
}
