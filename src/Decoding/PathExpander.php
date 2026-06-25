<?php

namespace Jayi\Toon\Decoding;

use Jayi\Toon\Exceptions\ToonStrictModeException;

/**
 * Expands dotted keys into nested object structures with deep merge.
 *
 * For example, `{"a.b": 1, "a.c": 2}` becomes `{"a": {"b": 1, "c": 2}}`.
 * Only expands keys where all segments are IdentifierSegments.
 * In strict mode, conflicts (object vs primitive at same path) throw an exception.
 */
class PathExpander
{
    private const string IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(
        private readonly bool $strict = true,
    ) {}

    public function expand(mixed $data): mixed
    {
        if (! is_array($data) || array_is_list($data)) {
            if (is_array($data)) {
                return array_map(fn (mixed $v) => $this->expand($v), $data);
            }

            return $data;
        }

        $expanded = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $value = $this->expand($value);

            if (! str_contains($key, '.')) {
                $this->setValue($expanded, [$key], $value);

                continue;
            }

            $segments = explode('.', $key);

            if (! $this->allIdentifierSegments($segments)) {
                $this->setValue($expanded, [$key], $value);

                continue;
            }

            $this->setValue($expanded, $segments, $value);
        }

        return $expanded;
    }

    /**
     * @param  string[]  $segments
     */
    private function setValue(array &$target, array $segments, mixed $value): void
    {
        $current = &$target;

        for ($i = 0; $i < count($segments) - 1; $i++) {
            $segment = $segments[$i];

            if (! array_key_exists($segment, $current)) {
                $current[$segment] = [];
            } elseif (! is_array($current[$segment]) || array_is_list($current[$segment])) {
                $path = implode('.', array_slice($segments, 0, $i + 1));

                if ($this->strict) {
                    throw new ToonStrictModeException("Expansion conflict at path '$path' (object vs primitive)");
                }

                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $lastSegment = end($segments);

        if (array_key_exists($lastSegment, $current)) {
            if (is_array($value) && ! array_is_list($value) && is_array($current[$lastSegment]) && ! array_is_list($current[$lastSegment])) {
                $current[$lastSegment] = $this->deepMerge($current[$lastSegment], $value);

                return;
            }

            $path = implode('.', $segments);

            if ($this->strict) {
                throw new ToonStrictModeException("Expansion conflict at path '$path'");
            }
        }

        $current[$lastSegment] = $value;
    }

    private function deepMerge(array $target, array $source): array
    {
        foreach ($source as $key => $value) {
            if (array_key_exists($key, $target) && is_array($target[$key]) && ! array_is_list($target[$key]) && is_array($value) && ! array_is_list($value)) {
                $target[$key] = $this->deepMerge($target[$key], $value);
            } else {
                if (array_key_exists($key, $target) && $this->strict) {
                    throw new ToonStrictModeException("Expansion conflict at key '$key'");
                }

                $target[$key] = $value;
            }
        }

        return $target;
    }

    /**
     * @param  string[]  $segments
     */
    private function allIdentifierSegments(array $segments): bool
    {
        foreach ($segments as $segment) {
            if (! preg_match(self::IDENTIFIER_PATTERN, $segment)) {
                return false;
            }
        }

        return true;
    }
}
