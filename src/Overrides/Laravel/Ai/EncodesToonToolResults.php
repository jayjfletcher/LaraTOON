<?php

namespace Jayi\Toon\Overrides\Laravel\Ai;

use Laravel\Ai\Tools\Request;

/**
 * Trait for Laravel AI tools that automatically TOON-encodes structured results.
 *
 * Implement `run()` instead of `handle()`. Array/object results are automatically
 * wrapped in a ToonToolResponse; string results pass through unchanged.
 */
trait EncodesToonToolResults
{
    /**
     * Execute the tool logic. Return any data type — arrays and objects
     * will be automatically TOON-encoded.
     */
    abstract protected function run(Request $request): mixed;

    /**
     * Handle the tool request, encoding structured results as TOON.
     */
    public function handle(Request $request): ToonToolResponse|string
    {
        $result = $this->run($request);

        if (is_array($result) || is_object($result)) {
            return new ToonToolResponse($result);
        }

        return (string) $result;
    }
}
