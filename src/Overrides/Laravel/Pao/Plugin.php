<?php

namespace Jayi\Toon\Overrides\Laravel\Pao;

use Laravel\Pao\Execution;
use Pest\Contracts\Plugins\AddsOutput;

/**
 * Pest plugin that converts PAO's JSON test output to TOON format.
 *
 * Activated by setting the `TOON_PAO_OUTPUT=true` environment variable.
 * Only runs when PAO (laravel/pao) is installed and an AI agent is detected.
 *
 * Attaches a stream filter to STDOUT during `addOutput` so that when PAO's
 * Pest plugin later writes its JSON result via Symfony's ConsoleOutput (which
 * writes directly to the STDOUT constant on Unix), the bytes flow through
 * the filter and are re-encoded as TOON.
 *
 * NOTE: intentionally does not implement `Terminable`. Pest runs `terminate()`
 * hooks in plugin registration order, which would remove Toon's filter
 * *before* PAO's own `terminate()` writes its JSON — meaning nothing would be
 * intercepted. The filter is left attached and cleaned up by PHP at shutdown.
 */
final class Plugin implements AddsOutput
{
    public function addOutput(int $exitCode): int
    {
        if (! class_exists(Execution::class) || ! Execution::running()) {
            return $exitCode;
        }

        if (! filter_var(getenv('TOON_PAO_OUTPUT') ?: ($_ENV['TOON_PAO_OUTPUT'] ?? 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return $exitCode;
        }

        if (! in_array('toon_output', stream_get_filters(), true)) {
            stream_filter_register('toon_output', ToonOutputFilter::class);
        }

        stream_filter_append(STDOUT, 'toon_output', STREAM_FILTER_WRITE);

        return $exitCode;
    }
}
