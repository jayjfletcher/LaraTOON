<?php

namespace Jayi\Toon\Decoding;

use Jayi\Toon\Enums\PathExpansion;

/**
 * Configuration options for the TOON decoder.
 */
readonly class DecoderOptions
{
    /**
     * @param  bool  $strict  Enforce strict mode validation (counts, indentation, escapes).
     * @param  int|null  $indent  Spaces per level, or null to auto-detect from first indented line.
     * @param  PathExpansion  $expandPaths  How to handle dotted keys (Off, Safe, or Auto).
     */
    public function __construct(
        public bool $strict = true,
        public ?int $indent = null,
        public PathExpansion $expandPaths = PathExpansion::Auto,
    ) {}
}
