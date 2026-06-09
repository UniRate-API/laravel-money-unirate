<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use UniRate\LaravelMoney\UniRateExchange;

final class ServiceProviderTest extends TestCase
{
    public function test_exchange_is_singleton(): void
    {
        $a = app(UniRateExchange::class);
        $b = app(UniRateExchange::class);

        $this->assertSame($a, $b);
    }

    public function test_alias_resolves(): void
    {
        $this->assertInstanceOf(UniRateExchange::class, app('unirate'));
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('unirate.api_key'));
        $this->assertSame('USD', config('unirate.base_currency'));
        $this->assertSame(3600, config('unirate.cache.ttl'));
    }

    public function test_config_is_publishable(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'unirate-config', '--no-interaction' => true])
            ->assertExitCode(0);
    }
}
