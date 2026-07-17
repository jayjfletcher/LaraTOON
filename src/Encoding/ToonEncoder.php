<?php

namespace Jayi\Toon\Encoding;

use BackedEnum;
use DateTimeInterface;
use Jayi\Toon\Enums\Delimiter;
use Jayi\Toon\Enums\KeyFolding;
use Jayi\Toon\Exceptions\ToonEncodeException;
use JsonSerializable;
use Stringable;
use Traversable;
use UnitEnum;

/**
 * Encodes PHP data structures to TOON format.
 *
 * Handles type normalization (DateTime, enums, JsonSerializable), tabular
 * detection for uniform object arrays, and all TOON array forms (inline,
 * tabular, mixed list, arrays of arrays).
 */
class ToonEncoder
{
    private Delimiter $documentDelimiter;

    private int $indent;

    public function __construct(
        private readonly EncoderOptions $options = new EncoderOptions,
    ) {
        $this->documentDelimiter = $options->delimiter;
        $this->indent = $options->indentSize;
    }

    public function encode(mixed $data): string
    {
        $normalized = $this->normalize($data);

        if ($this->options->keyFolding === KeyFolding::Safe) {
            $folder = new KeyFolder($this->options->flattenDepth);
            $normalized = $folder->fold($normalized);
        }

        return $this->encodeValue($normalized, 0, null);
    }

    private function encodeValue(mixed $value, int $depth, ?string $key): string
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $this->encodePrimitive($value, $depth, $key, $this->documentDelimiter);
        }

        if ($value instanceof \stdClass) {
            if ($key !== null) {
                return $this->prefix($depth).$this->encodeKeyStr($key).':';
            }

            return '';
        }

        if (! is_array($value)) {
            throw new ToonEncodeException('Unsupported value type: '.get_debug_type($value));
        }

        if ($value === []) {
            if ($key !== null) {
                return $this->prefix($depth).$this->encodeKeyStr($key).': []';
            }

            return '[]';
        }

        if (array_is_list($value)) {
            return $this->encodeArray($value, $depth, $key);
        }

        return $this->encodeObject($value, $depth, $key);
    }

    private function encodePrimitive(mixed $value, int $depth, ?string $key, Delimiter $activeDelimiter): string
    {
        $encoded = ValueEncoder::encode($value, $activeDelimiter, $this->documentDelimiter);

        if ($key !== null) {
            return $this->prefix($depth).$this->encodeKeyStr($key).': '.$encoded;
        }

        return $this->prefix($depth).$encoded;
    }

    private function encodeObject(array $map, int $depth, ?string $key): string
    {
        if (empty($map)) {
            if ($key !== null) {
                return $this->prefix($depth).$this->encodeKeyStr($key).':';
            }

            return '';
        }

        $lines = [];

        if ($key !== null) {
            $lines[] = $this->prefix($depth).$this->encodeKeyStr($key).':';
            $depth++;
        }

        foreach ($map as $k => $v) {
            $k = (string) $k;
            $lines[] = $this->encodeValue($v, $depth, $k);
        }

        return implode("\n", $lines);
    }

    private function encodeArray(array $items, int $depth, ?string $key): string
    {
        $delimSymbol = $this->documentDelimiter->headerSymbol();

        if ($this->allPrimitive($items)) {
            if ($this->allPrimitiveArrays($items)) {
                return $this->encodeArrayOfArrays($items, $depth, $key, $delimSymbol);
            }

            return $this->encodeInlinePrimitiveArray($items, $depth, $key, $delimSymbol);
        }

        $tabularFields = $this->detectTabular($items);

        if ($tabularFields !== null) {
            return $this->encodeTabularArray($items, $depth, $key, $tabularFields, $delimSymbol);
        }

        return $this->encodeMixedArray($items, $depth, $key, $delimSymbol);
    }

    private function encodeInlinePrimitiveArray(array $items, int $depth, ?string $key, string $delimSymbol): string
    {
        $count = count($items);
        $encoded = [];

        foreach ($items as $item) {
            $encoded[] = ValueEncoder::encode($item, $this->documentDelimiter, $this->documentDelimiter, true);
        }

        $header = '['.$count.$delimSymbol.']';
        $values = implode($this->documentDelimiter->value, $encoded);

        if ($key !== null) {
            return $this->prefix($depth).$this->encodeKeyStr($key).$header.': '.$values;
        }

        return $this->prefix($depth).$header.': '.$values;
    }

    private function encodeTabularArray(array $items, int $depth, ?string $key, array $fields, string $delimSymbol): string
    {
        $count = count($items);
        $encodedFields = array_map(fn (string $f) => $this->encodeKeyStr($f), $fields);
        $fieldList = implode($this->documentDelimiter->value, $encodedFields);
        $header = '['.$count.$delimSymbol.']{'.$fieldList.'}:';

        $lines = [];

        if ($key !== null) {
            $lines[] = $this->prefix($depth).$this->encodeKeyStr($key).$header;
        } else {
            $lines[] = $this->prefix($depth).$header;
        }

        foreach ($items as $item) {
            $row = [];

            foreach ($fields as $field) {
                $row[] = ValueEncoder::encode($item[$field] ?? null, $this->documentDelimiter, $this->documentDelimiter, true);
            }

            $lines[] = $this->prefix($depth + 1).implode($this->documentDelimiter->value, $row);
        }

        return implode("\n", $lines);
    }

    private function encodeArrayOfArrays(array $items, int $depth, ?string $key, string $delimSymbol): string
    {
        $count = count($items);
        $header = '['.$count.$delimSymbol.']:';
        $lines = [];

        if ($key !== null) {
            $lines[] = $this->prefix($depth).$this->encodeKeyStr($key).$header;
        } else {
            $lines[] = $this->prefix($depth).$header;
        }

        foreach ($items as $item) {
            $innerCount = count($item);
            $encoded = [];

            foreach ($item as $v) {
                $encoded[] = ValueEncoder::encode($v, $this->documentDelimiter, $this->documentDelimiter, true);
            }

            $values = implode($this->documentDelimiter->value, $encoded);
            $lines[] = $this->prefix($depth + 1).'- ['.$innerCount.$delimSymbol.']:'.($values === '' ? '' : ' '.$values);
        }

        return implode("\n", $lines);
    }

    private function encodeMixedArray(array $items, int $depth, ?string $key, string $delimSymbol): string
    {
        $count = count($items);
        $header = '['.$count.$delimSymbol.']:';
        $lines = [];

        if ($key !== null) {
            $lines[] = $this->prefix($depth).$this->encodeKeyStr($key).$header;
        } else {
            $lines[] = $this->prefix($depth).$header;
        }

        foreach ($items as $item) {
            $lines[] = $this->encodeListItem($item, $depth + 1);
        }

        return implode("\n", $lines);
    }

    private function encodeListItem(mixed $item, int $depth): string
    {
        if ($item instanceof \stdClass) {
            // Empty object as a list item: a bare dash, mirroring the empty-map
            // branch of encodeObjectListItem (a non-empty stdClass is normalized
            // to an array before reaching here).
            return $this->prefix($depth).'-';
        }

        if (is_array($item) && ! array_is_list($item)) {
            return $this->encodeObjectListItem($item, $depth);
        }

        if (is_array($item) && array_is_list($item)) {
            if ($this->allPrimitive($item)) {
                $count = count($item);
                $delimSymbol = $this->documentDelimiter->headerSymbol();
                $encoded = [];

                foreach ($item as $v) {
                    $encoded[] = ValueEncoder::encode($v, $this->documentDelimiter, $this->documentDelimiter, true);
                }

                $values = implode($this->documentDelimiter->value, $encoded);

                return $this->prefix($depth).'- ['.$count.$delimSymbol.']:'.($values === '' ? '' : ' '.$values);
            }

            $count = count($item);
            $delimSymbol = $this->documentDelimiter->headerSymbol();
            $lines = [$this->prefix($depth).'- ['.$count.$delimSymbol.']:'];

            foreach ($item as $subItem) {
                $lines[] = $this->encodeListItem($subItem, $depth + 1);
            }

            return implode("\n", $lines);
        }

        $encoded = ValueEncoder::encode($item, $this->documentDelimiter, $this->documentDelimiter);

        return $this->prefix($depth).'- '.$encoded;
    }

    private function encodeObjectListItem(array $map, int $depth): string
    {
        if (empty($map)) {
            return $this->prefix($depth).'-';
        }

        $keys = array_keys($map);
        $firstKey = (string) $keys[0];
        $firstValue = $map[$firstKey];

        $tabularFields = null;
        if (is_array($firstValue) && array_is_list($firstValue)) {
            $tabularFields = $this->detectTabular($firstValue);
        }

        if ($tabularFields !== null) {
            return $this->encodeListItemWithTabularFirst($map, $depth, $firstKey, $firstValue, $tabularFields);
        }

        $lines = [];
        $isFirst = true;

        foreach ($map as $k => $v) {
            $k = (string) $k;

            if ($isFirst) {
                $isFirst = false;

                // Encode the first key at $depth + 1 so any continuation lines
                // (nested objects or lists) land where the decoder expects them
                // ($depth + 2), then splice the "- " marker into the first line.
                $innerLines = explode("\n", $this->encodeValue($v, $depth + 1, $k));
                $innerLines[0] = $this->prefix($depth).'- '.ltrim($innerLines[0]);
                $lines[] = implode("\n", $innerLines);
            } else {
                $lines[] = $this->encodeValue($v, $depth + 1, $k);
            }
        }

        return implode("\n", $lines);
    }

    private function encodeListItemWithTabularFirst(array $map, int $depth, string $firstKey, array $firstValue, array $tabularFields): string
    {
        $count = count($firstValue);
        $delimSymbol = $this->documentDelimiter->headerSymbol();
        $encodedFields = array_map(fn (string $f) => $this->encodeKeyStr($f), $tabularFields);
        $fieldList = implode($this->documentDelimiter->value, $encodedFields);
        $header = '['.$count.$delimSymbol.']{'.$fieldList.'}:';

        $lines = [];
        $lines[] = $this->prefix($depth).'- '.$this->encodeKeyStr($firstKey).$header;

        foreach ($firstValue as $item) {
            $row = [];

            foreach ($tabularFields as $field) {
                $row[] = ValueEncoder::encode($item[$field] ?? null, $this->documentDelimiter, $this->documentDelimiter, true);
            }

            $lines[] = $this->prefix($depth + 2).implode($this->documentDelimiter->value, $row);
        }

        $keys = array_keys($map);

        for ($i = 1; $i < count($keys); $i++) {
            $k = (string) $keys[$i];
            $lines[] = $this->encodeValue($map[$k], $depth + 1, $k);
        }

        return implode("\n", $lines);
    }

    private function normalize(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }

            if ($value === -0.0 && fdiv(1.0, $value) === -INF) {
                return 0;
            }

            if (floor($value) === $value && abs($value) < PHP_INT_MAX) {
                return (int) $value;
            }

            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        if ($value instanceof BackedEnum) {
            return $this->normalize($value->value);
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof Traversable) {
            return array_map(fn (mixed $v) => $this->normalize($v), iterator_to_array($value));
        }

        if (is_array($value)) {
            return array_map(fn (mixed $v) => $this->normalize($v), $value);
        }

        if ($value instanceof \stdClass) {
            $arr = (array) $value;

            if (empty($arr)) {
                return new \stdClass;
            }

            return $this->normalize($arr);
        }

        if (is_object($value)) {
            return $this->normalize((array) $value);
        }

        throw new ToonEncodeException('Cannot encode value of type: '.get_debug_type($value));
    }

    /**
     * @return string[]|null Field names if tabular, null otherwise
     */
    private function detectTabular(array $items): ?array
    {
        if (empty($items)) {
            return null;
        }

        $fields = null;

        foreach ($items as $item) {
            if (! is_array($item) || array_is_list($item)) {
                return null;
            }

            foreach ($item as $v) {
                if (is_array($v) || is_object($v)) {
                    return null;
                }
            }

            $keys = array_keys($item);
            $keySet = $keys;
            sort($keySet);

            if ($fields === null) {
                $fields = $keySet;
            } elseif ($fields !== $keySet) {
                return null;
            }
        }

        $firstItem = $items[0];

        return array_map('strval', array_keys($firstItem));
    }

    private function allPrimitive(array $items): bool
    {
        foreach ($items as $item) {
            if (is_array($item) || is_object($item)) {
                return false;
            }
        }

        return true;
    }

    private function allPrimitiveArrays(array $items): bool
    {
        foreach ($items as $item) {
            if (! is_array($item) || ! array_is_list($item)) {
                return false;
            }

            if (! $this->allPrimitive($item)) {
                return false;
            }
        }

        return true;
    }

    private function prefix(int $depth): string
    {
        return str_repeat(' ', $depth * $this->indent);
    }

    /**
     * Encode an object key, emitting KeyFolder-produced dotted paths unquoted
     * while quoting everything else that needs it (including literal dotted keys).
     */
    private function encodeKeyStr(string $key): string
    {
        if ($this->options->keyFolding === KeyFolding::Safe && str_starts_with($key, KeyFolder::FOLDED_KEY_MARKER)) {
            return substr($key, strlen(KeyFolder::FOLDED_KEY_MARKER));
        }

        return ValueEncoder::encodeKey($key);
    }
}
