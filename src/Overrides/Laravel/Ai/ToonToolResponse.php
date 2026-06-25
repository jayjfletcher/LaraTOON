<?php

namespace Jayi\Toon\Overrides\Laravel\Ai;

use Jayi\Toon\Toon;
use Stringable;

/**
 * Stringable wrapper that encodes data as TOON for Laravel AI tool responses.
 *
 * Uses `Toon::smart()` to automatically choose the smallest encoding.
 * Returned from tools using the `EncodesToonToolResults` trait.
 */
readonly class ToonToolResponse implements Stringable
{
    private readonly string $encoded;

    public function __construct(mixed $data)
    {
        $this->encoded = Toon::smart($data);
    }

    public function __toString(): string
    {
        return $this->encoded;
    }
}
