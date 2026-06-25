<?php

namespace Jayi\Toon\Encoding;

use Jayi\Toon\Enums\Delimiter;
use Jayi\Toon\Enums\KeyFolding;

/**
 * Configuration options for the TOON encoder.
 *
 * @see EncoderOptions::compact() for a preset optimized for maximum token savings.
 */
readonly class EncoderOptions
{
    /**
     * @param  int  $indentSize  Spaces per indentation level (default 2).
     * @param  Delimiter  $delimiter  Delimiter for array values and tabular rows.
     * @param  KeyFolding  $keyFolding  Whether to collapse single-key chains into dotted paths.
     * @param  int|float  $flattenDepth  Maximum segments to fold (default INF for unlimited).
     */
    public function __construct(
        public int $indentSize = 2,
        public Delimiter $delimiter = Delimiter::Comma,
        public KeyFolding $keyFolding = KeyFolding::Off,
        public int|float $flattenDepth = INF,
    ) {}

    /**
     * Preset optimized for maximum token savings (indent=1, key folding enabled).
     */
    public static function compact(): self
    {
        return new self(
            indentSize: 1,
            keyFolding: KeyFolding::Safe,
        );
    }
}
