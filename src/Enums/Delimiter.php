<?php

namespace Jayi\Toon\Enums;

/**
 * Delimiter used to separate values in TOON arrays and tabular rows.
 *
 * Comma is the default. Tab and Pipe are alternatives declared in array headers.
 */
enum Delimiter: string
{
    case Comma = ',';
    case Tab = "\t";
    case Pipe = '|';

    /**
     * Get the symbol used inside bracket headers to declare this delimiter.
     *
     * Comma returns empty string (it's the default and omitted from headers).
     */
    public function headerSymbol(): string
    {
        return match ($this) {
            self::Comma => '',
            self::Tab => "\t",
            self::Pipe => '|',
        };
    }
}
