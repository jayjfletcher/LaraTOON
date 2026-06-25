<?php

namespace Jayi\Toon\Exceptions;

/**
 * Thrown when strict mode validation fails during decoding.
 *
 * Strict mode enforces count mismatches, indentation consistency,
 * invalid escapes, blank lines in arrays, and path expansion conflicts.
 */
class ToonStrictModeException extends ToonDecodeException {}
