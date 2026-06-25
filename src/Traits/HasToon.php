<?php

namespace Jayi\Toon\Traits;

use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Toon;

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
