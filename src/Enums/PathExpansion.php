<?php

namespace Jayi\Toon\Enums;

/**
 * Controls dotted key expansion behavior during decoding.
 *
 * - Off: Dotted keys like `a.b` are treated as literal keys.
 * - Safe: Dotted keys are expanded into nested objects with deep merge.
 * - Auto: Expands only when dotted keys are detected in the decoded data.
 */
enum PathExpansion: string
{
    case Off = 'off';
    case Safe = 'safe';
    case Auto = 'auto';
}
