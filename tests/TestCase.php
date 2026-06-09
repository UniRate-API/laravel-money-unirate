<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use UniRate\LaravelMoney\UniRateServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [UniRateServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['UniRate' => \UniRate\LaravelMoney\Facades\UniRate::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('unirate.api_key', 'test-key');
        $app['config']->set('unirate.base_url', 'https://api.unirateapi.com');
        $app['config']->set('unirate.base_currency', 'USD');
        $app['config']->set('unirate.cache.ttl', 3600);
        $app['config']->set('unirate.timeout', 5);
    }
}
