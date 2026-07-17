<?php

namespace Jayi\Toon;

use Illuminate\Container\Container;
use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Encoding\ToonEncoder;
use Jayi\Toon\Enums\Delimiter;
use Jayi\Toon\Enums\KeyFolding;
use Jayi\Toon\Enums\PathExpansion;

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
     *
     * When no options are given, defaults are read from the `toon` config
     * inside Laravel and fall back to the package defaults elsewhere.
     */
    public static function encode(mixed $data, ?EncoderOptions $options = null): string
    {
        $encoder = new ToonEncoder($options ?? self::defaultEncoderOptions());

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
        return self::shouldUseCompact($data)
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

        self::inspectStructure($data, 0, $maxDepth, $hasFoldable);

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

            self::inspectStructure($value, $depth + 1, $maxDepth, $hasFoldable);
        }
    }

    /**
     * Decode a TOON string back to PHP data.
     *
     * Auto-detects indent size and expands dotted keys by default. When no
     * options are given, defaults are read from the `toon` config inside
     * Laravel and fall back to the package defaults elsewhere.
     *
     * An empty string decodes to an empty array. Note that a root-level empty
     * object encodes to an empty string, so it decodes back as `[]`.
     */
    public static function decode(string $toon, ?DecoderOptions $options = null): mixed
    {
        $decoder = new ToonDecoder($options ?? self::defaultDecoderOptions());

        return $decoder->decode($toon);
    }

    /**
     * Estimate the percentage of tokens saved by encoding as TOON instead of JSON.
     *
     * Uses character count as a proxy for token count (~4 chars/token).
     *
     * @return array{json_chars: int, toon_chars: int, saved_chars: int, saved_percent: float}
     *
     * @throws \JsonException When the data cannot be encoded as JSON.
     */
    public static function savings(mixed $data, ?EncoderOptions $options = null): array
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $toon = static::encode($data, $options);

        $jsonLen = strlen($json);
        $toonLen = strlen($toon);
        $saved = $jsonLen - $toonLen;

        return [
            'json_chars' => $jsonLen,
            'toon_chars' => $toonLen,
            'saved_chars' => $saved,
            'saved_percent' => round(($saved / $jsonLen) * 100, 1),
        ];
    }

    /**
     * Build encoder defaults from the Laravel `toon` config when available.
     */
    private static function defaultEncoderOptions(): EncoderOptions
    {
        $config = self::laravelConfig();

        if ($config === null) {
            return new EncoderOptions;
        }

        $flattenDepth = $config['flatten_depth'] ?? INF;

        return new EncoderOptions(
            indentSize: (int) ($config['indent_size'] ?? 2),
            delimiter: Delimiter::fromName((string) ($config['delimiter'] ?? 'comma')),
            keyFolding: KeyFolding::from((string) ($config['key_folding'] ?? 'off')),
            flattenDepth: is_int($flattenDepth) ? $flattenDepth : (float) $flattenDepth,
        );
    }

    /**
     * Build decoder defaults from the Laravel `toon` config when available.
     */
    private static function defaultDecoderOptions(): DecoderOptions
    {
        $config = self::laravelConfig();

        if ($config === null) {
            return new DecoderOptions;
        }

        return new DecoderOptions(
            strict: (bool) ($config['strict'] ?? true),
            expandPaths: PathExpansion::from((string) ($config['expand_paths'] ?? 'auto')),
        );
    }

    /**
     * Read the `toon` config array from the Laravel container, or null when
     * running outside a booted Laravel application.
     *
     * @return array<string, mixed>|null
     */
    private static function laravelConfig(): ?array
    {
        if (! class_exists(Container::class)) {
            return null;
        }

        $container = Container::getInstance();

        if (! $container->bound('config')) {
            return null;
        }

        $config = $container->make('config')->get('toon');

        return is_array($config) ? $config : null;
    }
}
