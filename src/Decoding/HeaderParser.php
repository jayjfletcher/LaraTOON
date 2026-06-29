<?php

namespace Jayi\Toon\Decoding;

use Jayi\Toon\Enums\Delimiter;
use Jayi\Toon\Exceptions\ToonStrictModeException;

/**
 * Parses TOON array header lines like `key[N]{field1,field2}:`.
 *
 * Extracts the key, length, delimiter, field list, and any inline values.
 */
class HeaderParser
{
    /**
     * @return array{key: ?string, length: int, delimiter: Delimiter, fields: ?string[], inline: ?string}|null
     */
    public static function parse(string $line, ?int $lineNumber = null, bool $strict = false): ?array
    {
        $trimmed = trim($line);

        if ($trimmed === '') {
            return null;
        }

        $pos = self::findBracketStart($trimmed);

        if ($pos === null) {
            return null;
        }

        $key = $pos > 0 ? self::extractKey(substr($trimmed, 0, $pos), $lineNumber) : null;

        $remaining = substr($trimmed, $pos);

        $bracketEnd = strpos($remaining, ']');

        if ($bracketEnd === false) {
            return null;
        }

        $bracketContent = substr($remaining, 1, $bracketEnd - 1);
        $parsed = self::parseBracket($bracketContent, $strict, $lineNumber);

        if ($parsed === null) {
            return null;
        }

        $remaining = substr($remaining, $bracketEnd + 1);

        $fields = null;

        if (str_contains($remaining, '{')) {
            $braceStart = strpos($remaining, '{');

            if (trim(substr($remaining, 0, $braceStart)) !== '') {
                if ($strict) {
                    throw new ToonStrictModeException('Content between bracket and field list is not allowed', $lineNumber);
                }

                return null;
            }

            $braceEnd = strpos($remaining, '}', $braceStart);

            if ($braceEnd === false) {
                return null;
            }

            $fieldsContent = substr($remaining, $braceStart + 1, $braceEnd - $braceStart - 1);
            $fields = self::parseFields($fieldsContent, $parsed['delimiter'], $lineNumber, $strict);
            $remaining = substr($remaining, $braceEnd + 1);
        }

        $remaining = ltrim($remaining);

        if (! str_starts_with($remaining, ':')) {
            return null;
        }

        $afterColon = substr($remaining, 1);
        $inline = null;

        if ($afterColon !== '') {
            if (str_starts_with($afterColon, ' ')) {
                $inline = substr($afterColon, 1);
            } else {
                $inline = $afterColon;
            }
        }

        return [
            'key' => $key,
            'length' => $parsed['length'],
            'delimiter' => $parsed['delimiter'],
            'fields' => $fields,
            'inline' => $inline !== '' ? $inline : null,
        ];
    }

    private static function findBracketStart(string $line): ?int
    {
        if ($line[0] === '[') {
            return 0;
        }

        for ($i = 0; $i < strlen($line); $i++) {
            $ch = $line[$i];

            if ($ch === '"') {
                // Skip the quoted span, honoring backslash escapes, so brackets
                // inside quotes are not mistaken for the array start.
                for ($j = $i + 1; $j < strlen($line); $j++) {
                    if ($line[$j] === '\\') {
                        $j++;

                        continue;
                    }

                    if ($line[$j] === '"') {
                        $i = $j;

                        break;
                    }
                }

                continue;
            }

            if ($ch === '[') {
                return $i;
            }
        }

        return null;
    }

    private static function extractKey(string $raw, ?int $lineNumber): string
    {
        $raw = trim($raw);

        if (ValueDecoder::isQuoted($raw)) {
            return ValueDecoder::decodeQuoted($raw, $lineNumber);
        }

        return $raw;
    }

    /**
     * @return array{length: int, delimiter: Delimiter}|null
     */
    private static function parseBracket(string $content, bool $strict = false, ?int $lineNumber = null): ?array
    {
        $delimiter = Delimiter::Comma;

        if (str_ends_with($content, "\t")) {
            $delimiter = Delimiter::Tab;
            $content = rtrim($content, "\t");
        } elseif (str_ends_with($content, '|')) {
            $delimiter = Delimiter::Pipe;
            $content = rtrim($content, '|');
        }

        if (! preg_match('/^\d+$/', $content)) {
            return null;
        }

        if ($strict && strlen($content) > 1 && $content[0] === '0') {
            throw new ToonStrictModeException("Malformed bracket length '$content': leading zeros not allowed", $lineNumber);
        }

        return [
            'length' => (int) $content,
            'delimiter' => $delimiter,
        ];
    }

    /**
     * @return string[]
     */
    private static function parseFields(string $content, Delimiter $delimiter, ?int $lineNumber, bool $strict = false): array
    {
        if ($strict && $delimiter !== Delimiter::Comma) {
            $other = $delimiter === Delimiter::Pipe ? ',' : '|';
            $unquoted = self::splitDelimited($content, Delimiter::Comma);
            if (count($unquoted) > 1 || str_contains($content, $other)) {
                // Check for unquoted occurrence of a different structural delimiter
                $hasOther = false;
                $inQ = false;
                for ($i = 0; $i < strlen($content); $i++) {
                    if ($content[$i] === '"') {
                        $inQ = ! $inQ;

                        continue;
                    }
                    if (! $inQ && $content[$i] === $other) {
                        $hasOther = true;
                        break;
                    }
                }
                if ($hasOther) {
                    throw new ToonStrictModeException('Header delimiter mismatch in field list', $lineNumber);
                }
            }
        }

        $parts = self::splitDelimited($content, $delimiter);

        return array_map(function (string $field) use ($lineNumber) {
            $field = trim($field);

            if (ValueDecoder::isQuoted($field)) {
                return ValueDecoder::decodeQuoted($field, $lineNumber);
            }

            return $field;
        }, $parts);
    }

    /**
     * @return string[]
     */
    public static function splitDelimited(string $content, Delimiter $delimiter): array
    {
        $result = [];
        $current = '';
        $inQuotes = false;
        $len = strlen($content);
        $delimChar = $delimiter->value;

        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];

            if ($ch === '"') {
                $inQuotes = ! $inQuotes;
                $current .= $ch;

                continue;
            }

            if ($inQuotes) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $current .= $ch.$content[$i + 1];
                    $i++;

                    continue;
                }

                $current .= $ch;

                continue;
            }

            if ($ch === $delimChar) {
                $result[] = $current;
                $current = '';

                continue;
            }

            $current .= $ch;
        }

        $result[] = $current;

        return $result;
    }
}
