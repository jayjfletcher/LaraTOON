<?php

namespace Jayi\Toon\Enums;

/**
 * Controls key folding behavior during encoding.
 *
 * When Safe, single-key nested objects are collapsed into dotted paths
 * (e.g. `{"a":{"b":1}}` becomes `a.b: 1`).
 */
enum KeyFolding: string
{
    case Off = 'off';
    case Safe = 'safe';
}
