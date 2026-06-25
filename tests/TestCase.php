<?php

namespace Jayi\Toon\Tests;

use Jayi\Toon\ToonServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ToonServiceProvider::class,
        ];
    }
}
