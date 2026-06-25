<?php

namespace Jayi\Toon\Facades;

use Illuminate\Support\Facades\Facade;
use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Encoding\EncoderOptions;

/**
 * @method static string encode(mixed $data, ?EncoderOptions $options = null) Encode data to TOON format.
 * @method static string compact(mixed $data) Encode with compact settings (indent=1, key folding).
 * @method static string smart(mixed $data) Encode, auto-choosing compact or default for smallest output.
 * @method static mixed decode(string $toon, ?DecoderOptions $options = null) Decode a TOON string to PHP data.
 * @method static array savings(mixed $data, ?EncoderOptions $options = null) Estimate token savings vs JSON.
 *
 * @see \Jayi\Toon\Toon
 */
class Toon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jayi\Toon\Toon::class;
    }
}
