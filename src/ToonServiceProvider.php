<?php

namespace Jayi\Toon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Jayi\Toon\Encoding\EncoderOptions;
use Laravel\Mcp\Response as McpResponse;

/**
 * Registers TOON macros and configuration for Laravel.
 *
 * Macros added:
 * - `Collection::toToon()` — encode a collection as TOON.
 * - `Builder::toToon()` — encode a query builder's model as TOON.
 * - `JsonResponse::toToon()` — encode a JSON response as TOON.
 * - `Response::toon()` — MCP response helper (when laravel/mcp is installed).
 */
class ToonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/toon.php', 'toon');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/toon.php' => config_path('toon.php'),
        ], 'toon-config');

        Collection::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Collection $this */
            return Toon::encode($this->all(), $options);
        });

        Builder::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Collection $this */
            return Toon::encode($this->getModel()->toArray(), $options);
        });

        JsonResponse::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Collection $this */
            return Toon::encode($this->toArray(), $options);
        });

        if (class_exists('\Laravel\Mcp\Response')) {
            McpResponse::macro('toon', function (mixed $content) {
                return McpResponse::text(Toon::smart($content));
            });
        }
    }
}
