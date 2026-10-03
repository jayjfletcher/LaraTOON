<?php

namespace JayI\Toon\Domains;

use Illuminate\Support\ServiceProvider;
use JayI\Toon\Domains\Integration\IntegrationServiceProvider;

/**
 * Registers every domain service provider.
 *
 * The Encoding and Decoding domains are framework-free and need no
 * provider; only domains with Laravel wiring are listed here.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        IntegrationServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
