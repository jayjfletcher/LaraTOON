<?php

namespace JayI\Toon;

use Illuminate\Support\ServiceProvider;
use JayI\Toon\Domains\DomainServiceProvider;

/**
 * Registers TOON configuration and the package's domains with Laravel.
 *
 * The Laravel macros (`Collection::toToon()` and friends) are registered by
 * the Integration domain's provider.
 */
class ToonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/toon.php', 'toon');

        $this->app->register(DomainServiceProvider::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/toon.php' => config_path('toon.php'),
        ], 'toon-config');
    }
}
