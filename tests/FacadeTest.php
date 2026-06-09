<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use Illuminate\Support\Facades\Http;
use UniRate\LaravelMoney\Facades\UniRate;
use UniRate\LaravelMoney\UniRateExchange;

final class FacadeTest extends TestCase
{
    public function test_facade_resolves_to_exchange(): void
    {
        $this->assertInstanceOf(UniRateExchange::class, UniRate::getFacadeRoot());
    }

    public function test_facade_get_rate(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        $rate = UniRate::getRate('USD', 'EUR');

        $this->assertSame(0.92, $rate);
    }

    public function test_facade_get_rates(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92', 'GBP' => '0.79']], 200),
        ]);

        $rates = UniRate::getRates('USD');

        $this->assertCount(2, $rates);
    }

    public function test_facade_convert_amount(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        $result = UniRate::convertAmount(100.0, 'USD', 'EUR');

        $this->assertSame(92.0, $result);
    }

    public function test_facade_currencies(): void
    {
        Http::fake([
            '*/api/currencies*' => Http::response(['currencies' => ['USD', 'EUR']], 200),
        ]);

        $currencies = UniRate::currencies();

        $this->assertContains('USD', $currencies);
    }

    public function test_facade_flush_cache(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        UniRate::getRates('USD');
        UniRate::flushCache();
        UniRate::getRates('USD');

        Http::assertSentCount(2);
    }
}
