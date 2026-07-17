<?php

namespace Jayi\Toon\Decoding;

use Jayi\Toon\Exceptions\ToonDecodeException;

/**
 * Decodes individual TOON value tokens to PHP types.
 *
 * Handles type inference for unquoted tokens (booleans, null, numbers, strings)
 * and unescape for quoted strings per TOON spec section 4 and 7.1.
 */
class ValueDecoder
{
    public static function decode(string $token, ?int $line = null): mixed
    {
        $token = trim($token);

        if ($token === '') {
            return '';
        }

        if (self::isQuoted($token)) {
            return self::decodeQuoted($token, $line);
        }

        if ($token === 'true') {
            return true;
        }

        if ($token === 'false') {
            return false;
        }

        if ($token === 'null') {
            return null;
        }

        $numeric = self::tryParseNumber($token);

        if ($numeric !== false) {
            return $numeric;
        }

        return $token;
    }

    public static function isQuoted(string $value): bool
    {
        return strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"');
    }

    public static function decodeQuoted(string $token, ?int $line = null): string
    {
        if (! self::isQuoted($token)) {
            throw new ToonDecodeException('Expected quoted string', $line);
        }

        $inner = substr($token, 1, -1);
        $result = '';
        $len = strlen($inner);

        for ($i = 0; $i < $len; $i++) {
            if ($inner[$i] === '\\') {
                if ($i + 1 >= $len) {
                    throw new ToonDecodeException('Unterminated escape sequence', $line);
                }

                $next = $inner[$i + 1];
                if ($next === 'u') {
                    if ($i + 5 >= $len) {
                        throw new ToonDecodeException('Invalid escape sequence: \\u requires 4 hex digits', $line);
                    }
                    $hex = substr($inner, $i + 2, 4);
                    if (! preg_match('/^[0-9A-Fa-f]{4}$/', $hex)) {
                        throw new ToonDecodeException('Invalid escape sequence: \\u requires 4 hex digits', $line);
                    }
                    $codepoint = hexdec($hex);
                    if ($codepoint >= 0xDC00 && $codepoint <= 0xDFFF) {
                        throw new ToonDecodeException('Invalid escape sequence: lone surrogate \\u'.strtoupper($hex), $line);
                    }
                    if ($codepoint >= 0xD800 && $codepoint <= 0xDBFF) {
                        $low = self::parseLowSurrogate($inner, $i + 6, $hex, $line);
                        $codepoint = 0x10000 + (($codepoint - 0xD800) << 10) + ($low - 0xDC00);
                        $i += 6;
                    }
                    $result .= mb_chr($codepoint, 'UTF-8');
                    $i += 5;
                } else {
                    $result .= match ($next) {
                        '\\' => '\\',
                        '"' => '"',
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        default => throw new ToonDecodeException("Invalid escape sequence: \\$next", $line),
                    };
                    $i++;
                }
            } elseif ($inner[$i] === '"') {
                throw new ToonDecodeException('Unterminated string: unexpected quote', $line);
            } else {
                $result .= $inner[$i];
            }
        }

        return $result;
    }

    /**
     * Parse the low half of a UTF-16 surrogate pair starting at $offset.
     *
     * Throws when the high surrogate is not followed by a valid
     * `\uDC00`-`\uDFFF` escape, since that leaves it a lone surrogate.
     */
    private static function parseLowSurrogate(string $inner, int $offset, string $highHex, ?int $line): int
    {
        if (substr($inner, $offset, 2) !== '\\u') {
            throw new ToonDecodeException('Invalid escape sequence: lone surrogate \\u'.strtoupper($highHex), $line);
        }

        $hex = substr($inner, $offset + 2, 4);

        if (! preg_match('/^[0-9A-Fa-f]{4}$/', $hex)) {
            throw new ToonDecodeException('Invalid escape sequence: lone surrogate \\u'.strtoupper($highHex), $line);
        }

        $codepoint = hexdec($hex);

        if ($codepoint < 0xDC00 || $codepoint > 0xDFFF) {
            throw new ToonDecodeException('Invalid escape sequence: lone surrogate \\u'.strtoupper($highHex), $line);
        }

        return $codepoint;
    }

    private static function tryParseNumber(string $token): int|float|false
    {
        if (preg_match('/^0\d+$/', $token)) {
            return false;
        }

        if (preg_match('/^-0\d+$/', $token)) {
            return false;
        }

        if (! preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?$/', $token)) {
            return false;
        }

        if (str_contains($token, '.') || str_contains($token, 'e') || str_contains($token, 'E')) {
            $num = (float) $token;
        } else {
            $int = (int) $token;

            // Fall back to float when the integer overflows PHP_INT_MAX, so the
            // magnitude is preserved instead of silently clamping to the max int.
            $num = ((string) $int === $token) ? $int : (float) $token;
        }

        if (is_int($num)) {
            return $num;
        }

        if ($token === '-0') {
            return 0;
        }

        return (float) $num;
    }
}
