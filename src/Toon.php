<?php

namespace Jayi\Toon;

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Encoding\ToonEncoder;

/**
 * TOON (Token-Oriented Object Notation) encoder and decoder.
 *
 * Provides static methods for encoding PHP data to TOON format,
 * decoding TOON strings back to PHP, and estimating token savings.
 *
 * @see https://toonformat.dev
 */
class Toon
{
    /**
     * Encode data to TOON format.
     */
    public static function encode(mixed $data, ?EncoderOptions $options = null): string
    {
        $encoder = new ToonEncoder($options ?? new EncoderOptions);

        return $encoder->encode($data);
    }

    /**
     * Encode data using compact settings (indent=1, key folding enabled).
     */
    public static function compact(mixed $data): string
    {
        return static::encode($data, EncoderOptions::compact());
    }

    /**
     * Encode data, automatically choosing compact or default for the smallest output.
     */
    public static function smart(mixed $data): string
    {
        return static::shouldUseCompact($data)
            ? static::compact($data)
            : static::encode($data);
    }

    private static function shouldUseCompact(mixed $data): bool
    {
        if (! is_array($data) || array_is_list($data)) {
            return false;
        }

        $maxDepth = 0;
        $hasFoldable = false;

        static::inspectStructure($data, 0, $maxDepth, $hasFoldable);

        return $hasFoldable || $maxDepth >= 4;
    }

    private static function inspectStructure(array $data, int $depth, int &$maxDepth, bool &$hasFoldable): void
    {
        if ($hasFoldable) {
            return;
        }

        $maxDepth = max($maxDepth, $depth);

        foreach ($data as $value) {
            if (! is_array($value) || array_is_list($value)) {
                continue;
            }

            if (count($value) === 1) {
                $inner = reset($value);

                if (is_array($inner) && ! array_is_list($inner)) {
                    $hasFoldable = true;

                    return;
                }
            }

            static::inspectStructure($value, $depth + 1, $maxDepth, $hasFoldable);
        }
    }

    /**
     * Decode a TOON string back to PHP data.
     *
     * Auto-detects indent size and expands dotted keys by default.
     */
    public static function decode(string $toon, ?DecoderOptions $options = null): mixed
    {
        $decoder = new ToonDecoder($options ?? new DecoderOptions);

        return $decoder->decode($toon);
    }

    /**
     * Estimate the percentage of tokens saved by encoding as TOON instead of JSON.
     *
     * Uses character count as a proxy for token count (~4 chars/token).
     *
     * @return array{json_chars: int, toon_chars: int, saved_chars: int, saved_percent: float}
     */
    public static function savings(mixed $data, ?EncoderOptions $options = null): array
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $toon = static::encode($data, $options);

        $jsonLen = strlen($json);
        $toonLen = strlen($toon);
        $saved = $jsonLen - $toonLen;

        return [
            'json_chars' => $jsonLen,
            'toon_chars' => $toonLen,
            'saved_chars' => $saved,
            'saved_percent' => $jsonLen > 0 ? round(($saved / $jsonLen) * 100, 1) : 0.0,
        ];
    }
}
