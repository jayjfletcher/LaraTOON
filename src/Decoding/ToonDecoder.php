<?php

namespace Jayi\Toon\Decoding;

use Jayi\Toon\Enums\Delimiter;
use Jayi\Toon\Enums\PathExpansion;
use Jayi\Toon\Exceptions\ToonStrictModeException;

/**
 * Decodes TOON format strings into PHP data structures.
 *
 * Supports all TOON forms: objects, primitive arrays (inline), tabular arrays,
 * mixed/list arrays, arrays of arrays, and objects as list items.
 * Auto-detects indent size and optionally expands dotted keys.
 */
class ToonDecoder
{
    /** @var array{text: string, depth: int, num: int, blank: bool}[] */
    private array $lines = [];

    private int $pos = 0;

    public function __construct(
        private readonly DecoderOptions $options = new DecoderOptions,
    ) {}

    public function decode(string $toon): mixed
    {
        $this->lines = $this->parseLines($toon);
        $this->pos = 0;

        if (empty($this->lines)) {
            // An empty document decodes to an empty object per spec §5 (root forms).
            return new \stdClass;
        }

        $result = $this->decodeRoot();

        if ($this->options->strict) {
            $this->assertAllLinesConsumed();
        }

        return $this->applyExpansion($result);
    }

    /**
     * Ensure no non-blank lines remain after the document root has been decoded.
     */
    private function assertAllLinesConsumed(): void
    {
        for ($i = $this->pos; $i < count($this->lines); $i++) {
            $line = $this->lines[$i];

            if (! $line['blank']) {
                throw new ToonStrictModeException('Unexpected content after end of document', $line['num']);
            }
        }
    }

    /**
     * @return array{text: string, depth: int, num: int, blank: bool}[]
     */
    private function parseLines(string $input): array
    {
        $input = str_replace("\r\n", "\n", $input);
        $input = rtrim($input, "\n");

        if ($input === '') {
            return [];
        }

        $rawLines = explode("\n", $input);
        $indent = $this->options->indentSize ?? $this->detectIndent($rawLines);
        $parsed = [];

        foreach ($rawLines as $i => $line) {
            $lineNum = $i + 1;

            if (trim($line) === '') {
                $parsed[] = ['text' => '', 'depth' => 0, 'num' => $lineNum, 'blank' => true];

                continue;
            }

            if ($this->options->strict) {
                if (preg_match('/\s+$/', $line)) {
                    throw new ToonStrictModeException('Trailing spaces are not allowed', $lineNum);
                }

                if (preg_match("/^\t/", $line)) {
                    throw new ToonStrictModeException('Tabs are not allowed in indentation', $lineNum);
                }
            }

            $stripped = ltrim($line);
            $spaces = strlen($line) - strlen($stripped);

            if ($this->options->strict && $indent > 0 && $spaces % $indent !== 0) {
                throw new ToonStrictModeException("Indentation must be an exact multiple of $indent spaces", $lineNum);
            }

            $depth = $indent > 0 ? intdiv($spaces, $indent) : 0;

            $parsed[] = ['text' => $stripped, 'depth' => $depth, 'num' => $lineNum, 'blank' => false];
        }

        return $parsed;
    }

    /**
     * @param  string[]  $lines
     */
    private function detectIndent(array $lines): int
    {
        foreach ($lines as $line) {
            if ($line === '' || trim($line) === '') {
                continue;
            }

            $stripped = ltrim($line);
            $spaces = strlen($line) - strlen($stripped);

            if ($spaces > 0) {
                return $spaces;
            }
        }

        return 2;
    }

    private function decodeRoot(): mixed
    {
        $nonBlank = array_filter($this->lines, fn (array $l) => ! $l['blank']);

        if (empty($nonBlank)) {
            // Blank-only document is an empty document → empty object (spec §5).
            return new \stdClass;
        }

        $first = reset($nonBlank);
        $firstText = $first['text'];

        $header = HeaderParser::parse($firstText, $first['num'], $this->options->strict);

        if ($header !== null && $first['depth'] === 0 && $header['key'] === null) {
            return $this->decodeRootArray($header);
        }

        if ($header !== null && $first['depth'] === 0) {
            return $this->decodeObject(0);
        }

        if ($firstText === '[]' && count(array_values($nonBlank)) === 1) {
            $this->pos = count($this->lines);

            return [];
        }

        if ($this->findUnquotedColon($firstText) !== false) {
            return $this->decodeObject(0);
        }

        $nonBlankList = array_values($nonBlank);

        if (count($nonBlankList) === 1) {
            $this->pos = count($this->lines);

            return ValueDecoder::decode($firstText, $first['num']);
        }

        if ($this->options->strict && count($nonBlankList) >= 2) {
            $allKv = true;

            foreach ($nonBlankList as $l) {
                if ($l['depth'] !== 0 || (! str_contains($l['text'], ':') && HeaderParser::parse($l['text'], $l['num'], $this->options->strict) === null)) {
                    $allKv = false;

                    break;
                }
            }

            if (! $allKv) {
                throw new ToonStrictModeException('Invalid root structure: multiple non-key-value lines at depth 0', $first['num']);
            }
        }

        return $this->decodeObject(0);
    }

    private function decodeRootArray(array $header): array
    {
        if ($header['inline'] !== null) {
            $result = $this->decodeInlineValues($header['inline'], $header['delimiter'], $header['fields'], $header['length'], $this->lines[$this->pos]['num']);
            $this->pos++;

            return $result;
        }

        $this->pos++;

        if ($header['fields'] !== null) {
            return $this->decodeTabularRows($header['fields'], $header['delimiter'], $header['length'], 1);
        }

        return $this->decodeListItems($header['length'], 1);
    }

    private function decodeObject(int $depth): array
    {
        $result = [];

        while ($this->pos < count($this->lines)) {
            $line = $this->lines[$this->pos];

            if ($line['blank']) {
                $this->pos++;

                continue;
            }

            if ($line['depth'] < $depth) {
                break;
            }

            if ($line['depth'] > $depth) {
                break;
            }

            $text = $line['text'];
            $lineNum = $line['num'];

            $header = HeaderParser::parse($text, $lineNum, $this->options->strict);

            if ($header !== null && $header['key'] !== null) {
                if ($this->options->strict && array_key_exists($header['key'], $result)) {
                    throw new ToonStrictModeException("Duplicate key '{$header['key']}'", $lineNum);
                }

                $this->pos++;
                $result[$header['key']] = $this->decodeArrayValue($header, $depth);

                continue;
            }

            $colonPos = $this->findUnquotedColon($text);

            if ($colonPos === false) {
                if ($this->options->strict) {
                    throw new ToonStrictModeException('Missing colon after key', $lineNum);
                }

                $this->pos++;

                continue;
            }

            $rawKey = trim(substr($text, 0, $colonPos));
            $afterColon = substr($text, $colonPos + 1);

            $key = $this->decodeKey($rawKey, $lineNum);

            if ($this->options->strict && array_key_exists($key, $result)) {
                throw new ToonStrictModeException("Duplicate key '$key'", $lineNum);
            }

            if (trim($afterColon) === '') {
                $this->pos++;
                $nextDepthLines = $this->peekNextDepthLines($depth + 1);

                if (empty($nextDepthLines)) {
                    $result[$key] = new \stdClass;
                } else {
                    $result[$key] = $this->decodeObject($depth + 1);
                }

                continue;
            }

            $value = ltrim($afterColon);

            if ($value === '[]') {
                $this->pos++;
                $result[$key] = [];

                continue;
            }

            $result[$key] = ValueDecoder::decode($value, $lineNum);
            $this->pos++;
        }

        return $result;
    }

    private function decodeArrayValue(array $header, int $parentDepth): array
    {
        if ($header['inline'] !== null) {
            return $this->decodeInlineValues($header['inline'], $header['delimiter'], $header['fields'], $header['length'], $this->lines[$this->pos - 1]['num']);
        }

        $childDepth = $parentDepth + 1;

        if ($header['fields'] !== null) {
            return $this->decodeTabularRows($header['fields'], $header['delimiter'], $header['length'], $childDepth);
        }

        return $this->decodeListItems($header['length'], $childDepth);
    }

    private function decodeInlineValues(string $inline, Delimiter $delimiter, ?array $fields, int $expectedCount, int $lineNum): array
    {
        $parts = HeaderParser::splitDelimited($inline, $delimiter);
        $values = array_map(fn (string $v) => ValueDecoder::decode(trim($v), $lineNum), $parts);

        if ($this->options->strict && count($values) !== $expectedCount) {
            throw new ToonStrictModeException("Expected $expectedCount values, but got ".count($values), $lineNum);
        }

        if ($fields !== null) {
            $result = [];

            foreach ($fields as $i => $field) {
                $result[$field] = $values[$i] ?? null;
            }

            return [$result];
        }

        return $values;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeTabularRows(array $fields, Delimiter $delimiter, int $expectedCount, int $depth): array
    {
        $rows = [];
        $firstRowLine = null;

        while ($this->pos < count($this->lines)) {
            $line = $this->lines[$this->pos];

            if ($line['blank']) {
                if ($this->options->strict && $firstRowLine !== null) {
                    throw new ToonStrictModeException('Blank lines are not allowed inside tabular rows', $line['num']);
                }

                $this->pos++;

                continue;
            }

            if ($line['depth'] < $depth) {
                break;
            }

            if ($line['depth'] > $depth) {
                break;
            }

            $text = $line['text'];

            if ($this->isKeyValueLine($text, $delimiter)) {
                break;
            }

            $firstRowLine ??= $line['num'];
            $parts = HeaderParser::splitDelimited($text, $delimiter);
            $values = array_map(fn (string $v) => ValueDecoder::decode(trim($v), $line['num']), $parts);

            if ($this->options->strict && count($values) !== count($fields)) {
                throw new ToonStrictModeException('Expected '.count($fields).' values in row, but got '.count($values), $line['num']);
            }

            $row = [];

            foreach ($fields as $i => $field) {
                $row[$field] = $values[$i] ?? null;
            }

            $rows[] = $row;
            $this->pos++;
        }

        if ($this->options->strict && count($rows) !== $expectedCount) {
            throw new ToonStrictModeException("Expected $expectedCount tabular rows, but got ".count($rows), $firstRowLine ?? $this->currentLineNumber());
        }

        return $rows;
    }

    private function decodeListItems(int $expectedCount, int $depth): array
    {
        $items = [];
        $firstItemLine = null;

        while ($this->pos < count($this->lines)) {
            $line = $this->lines[$this->pos];

            if ($line['blank']) {
                if ($this->options->strict && $firstItemLine !== null) {
                    throw new ToonStrictModeException('Blank lines are not allowed inside array items', $line['num']);
                }

                $this->pos++;

                continue;
            }

            if ($line['depth'] < $depth) {
                break;
            }

            if ($line['depth'] !== $depth) {
                break;
            }

            $text = $line['text'];

            if (! str_starts_with($text, '- ') && $text !== '-') {
                break;
            }

            $firstItemLine ??= $line['num'];

            if ($text === '-') {
                // A bare dash is an empty-object list item per spec §10, not an
                // empty array — this keeps `[{}]` round-tripping.
                $items[] = new \stdClass;
                $this->pos++;

                continue;
            }

            $after = substr($text, 2);
            $items[] = $this->decodeListItemContent($after, $line['num'], $depth);
        }

        if ($this->options->strict && count($items) !== $expectedCount) {
            throw new ToonStrictModeException("Expected $expectedCount list items, but got ".count($items), $firstItemLine ?? $this->currentLineNumber());
        }

        return $items;
    }

    private function decodeListItemContent(string $content, int $lineNum, int $depth): mixed
    {
        $header = HeaderParser::parse($content, $lineNum, $this->options->strict);

        if ($header !== null && $header['key'] === null) {
            if ($header['inline'] !== null) {
                $this->pos++;

                return $this->decodeInlineValues($header['inline'], $header['delimiter'], $header['fields'], $header['length'], $lineNum);
            }

            $this->pos++;

            if ($header['fields'] !== null) {
                return $this->decodeTabularRows($header['fields'], $header['delimiter'], $header['length'], $depth + 1);
            }

            return $this->decodeListItems($header['length'], $depth + 1);
        }

        if ($header !== null) {
            $this->pos++;
            $obj = [];

            if ($header['fields'] !== null && $header['inline'] === null) {
                $obj[$header['key']] = $this->decodeTabularRows($header['fields'], $header['delimiter'], $header['length'], $depth + 2);
            } elseif ($header['inline'] !== null) {
                $obj[$header['key']] = $this->decodeInlineValues($header['inline'], $header['delimiter'], $header['fields'], $header['length'], $lineNum);
            } else {
                $obj[$header['key']] = $this->decodeListItems($header['length'], $depth + 2);
            }

            $this->decodeObjectFields($obj, $depth + 1);

            return $obj;
        }

        $colonPos = $this->findUnquotedColon($content);

        if ($colonPos !== false) {
            $this->pos++;
            $obj = [];
            $rawKey = trim(substr($content, 0, $colonPos));
            $afterColon = substr($content, $colonPos + 1);

            $key = $this->decodeKey($rawKey, $lineNum);

            $value = ltrim($afterColon);

            if ($value === '') {
                $nextDepthLines = $this->peekNextDepthLines($depth + 2);

                if (empty($nextDepthLines)) {
                    $obj[$key] = new \stdClass;
                } else {
                    $obj[$key] = $this->decodeObject($depth + 2);
                }
            } elseif ($value === '[]') {
                $obj[$key] = [];
            } else {
                $obj[$key] = ValueDecoder::decode($value, $lineNum);
            }

            $this->decodeObjectFields($obj, $depth + 1);

            return $obj;
        }

        $this->pos++;

        return ValueDecoder::decode($content, $lineNum);
    }

    private function decodeObjectFields(array &$obj, int $depth): void
    {
        while ($this->pos < count($this->lines)) {
            $line = $this->lines[$this->pos];

            if ($line['blank']) {
                $this->pos++;

                continue;
            }

            if ($line['depth'] !== $depth) {
                break;
            }

            $text = $line['text'];
            $lineNum = $line['num'];

            $header = HeaderParser::parse($text, $lineNum, $this->options->strict);

            if ($header !== null && $header['key'] !== null) {
                $this->pos++;
                $obj[$header['key']] = $this->decodeArrayValue($header, $depth);

                continue;
            }

            $colonPos = $this->findUnquotedColon($text);

            if ($colonPos === false) {
                break;
            }

            $rawKey = trim(substr($text, 0, $colonPos));
            $afterColon = substr($text, $colonPos + 1);

            $key = $this->decodeKey($rawKey, $lineNum);

            if (trim($afterColon) === '') {
                $this->pos++;
                $nextDepthLines = $this->peekNextDepthLines($depth + 1);

                if (empty($nextDepthLines)) {
                    $obj[$key] = new \stdClass;
                } else {
                    $obj[$key] = $this->decodeObject($depth + 1);
                }

                continue;
            }

            $value = ltrim($afterColon);

            if ($value === '[]') {
                $this->pos++;
                $obj[$key] = [];

                continue;
            }

            $obj[$key] = ValueDecoder::decode($value, $lineNum);
            $this->pos++;
        }
    }

    private function isKeyValueLine(string $text, Delimiter $delimiter): bool
    {
        $delimPos = $this->findUnquotedChar($text, $delimiter->value);
        $colonPos = $this->findUnquotedColon($text);

        if ($colonPos === false) {
            return false;
        }

        if ($delimPos === false) {
            return true;
        }

        return $colonPos < $delimPos;
    }

    /**
     * Decode an object key token, marking quoted dotted keys as literal so
     * path expansion never splits them into nested paths.
     */
    private function decodeKey(string $rawKey, int $lineNum): string
    {
        if (! ValueDecoder::isQuoted($rawKey)) {
            return $rawKey;
        }

        $key = ValueDecoder::decodeQuoted($rawKey, $lineNum);

        return str_contains($key, '.') ? PathExpander::LITERAL_KEY_MARKER.$key : $key;
    }

    /**
     * Line number at the current parse position, or of the last line when
     * the position is already past the end of the document.
     */
    private function currentLineNumber(): ?int
    {
        if (isset($this->lines[$this->pos])) {
            return $this->lines[$this->pos]['num'];
        }

        $last = end($this->lines);

        return $last === false ? null : $last['num'];
    }

    private function findUnquotedColon(string $text): int|false
    {
        return $this->findUnquotedChar($text, ':');
    }

    private function findUnquotedChar(string $text, string $char): int|false
    {
        $inQuotes = false;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];

            if ($ch === '"') {
                $inQuotes = ! $inQuotes;

                continue;
            }

            if ($inQuotes) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $i++;
                }

                continue;
            }

            if ($ch === $char) {
                return $i;
            }
        }

        return false;
    }

    /**
     * @return array{text: string, depth: int, num: int, blank: bool}[]
     */
    private function peekNextDepthLines(int $depth): array
    {
        $result = [];

        for ($i = $this->pos; $i < count($this->lines); $i++) {
            $line = $this->lines[$i];

            if ($line['blank']) {
                continue;
            }

            if ($line['depth'] === $depth) {
                $result[] = $line;
            } else {
                break;
            }
        }

        return $result;
    }

    private function applyExpansion(mixed $result): mixed
    {
        $mode = $this->options->expandPaths;

        if ($mode === PathExpansion::Off) {
            return $this->stripLiteralKeyMarkers($result);
        }

        if ($mode === PathExpansion::Auto && ! $this->hasDottedKeys($result)) {
            return $this->stripLiteralKeyMarkers($result);
        }

        $expander = new PathExpander($this->options->strict);

        return $expander->expand($result);
    }

    /**
     * Remove literal-key markers from quoted dotted keys when no path
     * expansion runs (the expander strips them itself).
     */
    private function stripLiteralKeyMarkers(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map(fn (mixed $v) => $this->stripLiteralKeyMarkers($v), $data);
        }

        $result = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;

            if (str_starts_with($key, PathExpander::LITERAL_KEY_MARKER)) {
                $key = substr($key, strlen(PathExpander::LITERAL_KEY_MARKER));
            }

            $result[$key] = $this->stripLiteralKeyMarkers($value);
        }

        return $result;
    }

    private function hasDottedKeys(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        // Recurse through lists too: a dotted key can live inside a list-item
        // object, and Auto expansion must still trigger for it.
        if (array_is_list($data)) {
            foreach ($data as $value) {
                if ($this->hasDottedKeys($value)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($data as $key => $value) {
            if (str_contains((string) $key, '.') && preg_match('/^[A-Za-z_][A-Za-z0-9_]*\./', (string) $key)) {
                return true;
            }

            if ($this->hasDottedKeys($value)) {
                return true;
            }
        }

        return false;
    }
}
