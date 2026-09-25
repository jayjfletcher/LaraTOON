<?php

namespace JayI\Toon\Traits;

use JayI\Toon\Encoding\EncoderOptions;
use JayI\Toon\Toon;

/**
 * Adds a `toToon()` method to Eloquent models or any class with `toArray()`.
 *
 * Usage: `$model->toToon()` or `$model->toToon(EncoderOptions::compact())`.
 */
trait HasToon
{
    public function toToon(?EncoderOptions $options = null): string
    {
        return Toon::encode($this->toArray(), $options);
    }
}
