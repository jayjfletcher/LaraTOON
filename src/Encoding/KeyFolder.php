<?php

namespace Jayi\Toon\Encoding;

/**
 * Collapses chains of single-key nested objects into dotted-path keys.
 *
 * For example, `{"a": {"b": {"c": 1}}}` becomes `{"a.b.c": 1}`.
 * Only folds IdentifierSegment keys (letters, digits, underscores).
 */
class KeyFolder
{
    /**
     * Prefix marking keys produced by folding, so the encoder can emit them
     * unquoted while literal dotted keys from the input data stay quoted.
     */
    public const string FOLDED_KEY_MARKER = "\0";

    private const IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(
        private readonly int|float $flattenDepth = INF,
    ) {}

    public function fold(mixed $data): mixed
    {
        if (! is_array($data) || array_is_list($data)) {
            if (is_array($data)) {
                return array_map(fn (mixed $item) => $this->fold($item), $data);
            }

            return $data;
        }

        $inputKeys = [];

        foreach (array_keys($data) as $inputKey) {
            $inputKeys[(string) $inputKey] = true;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $chain = $this->collectChain($key, $value);

            if (count($chain['segments']) > 1) {
                $depth = (int) min(count($chain['segments']), $this->flattenDepth);

                if ($depth >= 2 && $this->canFold($chain['segments'], $depth, $result, $inputKeys)) {
                    $foldedKey = implode('.', array_slice($chain['segments'], 0, $depth));
                    $remaining = $this->buildRemainder(array_slice($chain['segments'], $depth), $chain['leaf']);
                    $result[self::FOLDED_KEY_MARKER.$foldedKey] = $this->fold($remaining);

                    continue;
                }
            }

            $result[$key] = $this->fold($value);
        }

        return $result;
    }

    /**
     * @return array{segments: string[], leaf: mixed}
     */
    private function collectChain(string $key, mixed $value): array
    {
        $segments = [$key];

        while (is_array($value) && ! array_is_list($value) && count($value) === 1) {
            $innerKey = (string) array_key_first($value);
            $segments[] = $innerKey;
            $value = $value[$innerKey];
        }

        return ['segments' => $segments, 'leaf' => $value];
    }

    /**
     * @param  string[]  $segments
     * @param  array<string, mixed>  $existingSiblings
     * @param  array<string, true>  $inputKeys
     */
    private function canFold(array $segments, int $depth, array $existingSiblings, array $inputKeys): bool
    {
        for ($i = 0; $i < $depth; $i++) {
            if (! preg_match(self::IDENTIFIER_PATTERN, $segments[$i])) {
                return false;
            }
        }

        $foldedKey = implode('.', array_slice($segments, 0, $depth));

        if (isset($inputKeys[$foldedKey])) {
            return false;
        }

        if (array_key_exists($foldedKey, $existingSiblings) || array_key_exists(self::FOLDED_KEY_MARKER.$foldedKey, $existingSiblings)) {
            return false;
        }

        return true;
    }

    private function buildRemainder(array $segments, mixed $leaf): mixed
    {
        if (empty($segments)) {
            return $leaf;
        }

        $result = $leaf;

        foreach (array_reverse($segments) as $segment) {
            $result = [$segment => $result];
        }

        return $result;
    }
}
