<?php

namespace JayI\Toon\Domains\Integration;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use JayI\Toon\Domains\Encoding\Data\EncoderOptions;
use JayI\Toon\Toon;
use Laravel\Mcp\Response as McpResponse;

/**
 * Registers the TOON macros on Laravel types.
 *
 * Macros added:
 * - `Collection::toToon()` — encode a collection as TOON.
 * - `Builder::toToon()` — encode a query builder's model as TOON.
 * - `JsonResponse::toToon()` — encode a JSON response as TOON.
 * - `Response::toon()` — MCP response helper (when laravel/mcp is installed).
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Collection::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Collection $this */
            return Toon::encode($this->all(), $options);
        });

        Builder::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Builder $this */
            return Toon::encode($this->get()->toArray(), $options);
        });

        JsonResponse::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var JsonResponse $this */
            return Toon::encode($this->getData(true) ?? [], $options);
        });

        if (class_exists('\Laravel\Mcp\Response')) {
            McpResponse::macro('toon', function (mixed $content) {
                return McpResponse::text(Toon::smart($content));
            });
        }
    }
}
