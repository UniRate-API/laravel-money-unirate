<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UniRate\LaravelMoney\UniRateExchange;
use UniRate\LaravelMoney\UniRateException;

final class UniRateExchangeTest extends TestCase
{
    private function fakeRatesResponse(string $base = 'USD', array $rates = null): void
    {
        $rates ??= ['EUR' => '0.92', 'GBP' => '0.79', 'JPY' => '157.50', 'CAD' => '1.36'];

        Http::fake([
            '*/api/rates*' => Http::response(['rates' => $rates], 200),
        ]);
    }

    private function fakeCurrenciesResponse(array $currencies = null): void
    {
        $currencies ??= ['USD', 'EUR', 'GBP', 'JPY', 'CAD'];

        Http::fake([
            '*/api/currencies*' => Http::response(['currencies' => $currencies], 200),
        ]);
    }

    // ---------------------------------------------------------------
    // getRate()
    // ---------------------------------------------------------------

    public function test_get_rate_returns_float(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $rate = $exchange->getRate('USD', 'EUR');

        $this->assertSame(0.92, $rate);
    }

    public function test_get_rate_same_currency_returns_one(): void
    {
        $exchange = app(UniRateExchange::class);

        $rate = $exchange->getRate('USD', 'USD');

        $this->assertSame(1.0, $rate);
        Http::assertNothingSent();
    }

    public function test_get_rate_case_insensitive(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $rate = $exchange->getRate('usd', 'eur');

        $this->assertSame(0.92, $rate);
    }

    public function test_get_rate_throws_for_unknown_currency(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('Currency pair USD/XYZ not available');

        $exchange->getRate('USD', 'XYZ');
    }

    // ---------------------------------------------------------------
    // getRates()
    // ---------------------------------------------------------------

    public function test_get_rates_returns_full_map(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $rates = $exchange->getRates('USD');

        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertSame(0.92, $rates['EUR']);
        $this->assertSame(0.79, $rates['GBP']);
    }

    public function test_get_rates_defaults_to_base_currency(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $rates = $exchange->getRates();

        $this->assertNotEmpty($rates);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'from=USD'));
    }

    public function test_get_rates_sends_api_key(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->getRates('USD');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api_key=test-key'));
    }

    public function test_get_rates_sends_accept_header(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->getRates('USD');

        Http::assertSent(fn ($request) => $request->header('Accept')[0] === 'application/json');
    }

    public function test_get_rates_sends_user_agent(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->getRates('USD');

        Http::assertSent(fn ($request) => str_starts_with($request->header('User-Agent')[0], 'laravel-money-unirate/'));
    }

    // ---------------------------------------------------------------
    // Caching
    // ---------------------------------------------------------------

    public function test_rates_are_cached(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->getRates('USD');
        $exchange->getRates('USD');

        Http::assertSentCount(1);
    }

    public function test_different_bases_cached_separately(): void
    {
        Http::fake([
            '*/api/rates*' => Http::sequence()
                ->push(['rates' => ['EUR' => '0.92']], 200)
                ->push(['rates' => ['USD' => '1.09']], 200),
        ]);

        $exchange = app(UniRateExchange::class);

        $usdRates = $exchange->getRates('USD');
        $eurRates = $exchange->getRates('EUR');

        Http::assertSentCount(2);
        $this->assertArrayHasKey('EUR', $usdRates);
        $this->assertArrayHasKey('USD', $eurRates);
    }

    public function test_cache_disabled_when_ttl_zero(): void
    {
        config(['unirate.cache.ttl' => 0]);

        $this->fakeRatesResponse();
        $exchange = app()->make(UniRateExchange::class);

        $exchange->getRates('USD');
        $exchange->getRates('USD');

        Http::assertSentCount(2);
    }

    public function test_flush_cache_clears_cached_data(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->getRates('USD');
        $exchange->flushCache();
        $exchange->getRates('USD');

        Http::assertSentCount(2);
    }

    // ---------------------------------------------------------------
    // convert()
    // ---------------------------------------------------------------

    public function test_convert_money_instance(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $usd = new Money(1000, new Currency('USD'));
        $eur = $exchange->convert($usd, 'EUR');

        $this->assertSame('EUR', $eur->getCurrency()->getCurrency());
        $this->assertSame('920', (string) $eur->getAmount());
    }

    public function test_convert_accepts_currency_object(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $usd = new Money(5000, new Currency('USD'));
        $eur = $exchange->convert($usd, new Currency('EUR'));

        $this->assertSame('EUR', $eur->getCurrency()->getCurrency());
    }

    public function test_convert_preserves_precision(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['JPY' => '157.50']], 200),
        ]);

        $exchange = app(UniRateExchange::class);

        $usd = new Money(1000, new Currency('USD'));
        $jpy = $exchange->convert($usd, 'JPY');

        $this->assertSame('JPY', $jpy->getCurrency()->getCurrency());
    }

    // ---------------------------------------------------------------
    // convertAmount()
    // ---------------------------------------------------------------

    public function test_convert_amount(): void
    {
        $this->fakeRatesResponse();
        $exchange = app(UniRateExchange::class);

        $result = $exchange->convertAmount(100.0, 'USD', 'EUR');

        $this->assertSame(92.0, $result);
    }

    public function test_convert_amount_same_currency(): void
    {
        $exchange = app(UniRateExchange::class);

        $result = $exchange->convertAmount(50.0, 'EUR', 'EUR');

        $this->assertSame(50.0, $result);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------
    // currencies()
    // ---------------------------------------------------------------

    public function test_currencies_returns_list(): void
    {
        $this->fakeCurrenciesResponse();
        $exchange = app(UniRateExchange::class);

        $currencies = $exchange->currencies();

        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertCount(5, $currencies);
    }

    public function test_currencies_are_cached(): void
    {
        $this->fakeCurrenciesResponse();
        $exchange = app(UniRateExchange::class);

        $exchange->currencies();
        $exchange->currencies();

        Http::assertSentCount(1);
    }

    // ---------------------------------------------------------------
    // Error handling
    // ---------------------------------------------------------------

    public function test_401_throws_authentication_error(): void
    {
        Http::fake(['*/api/rates*' => Http::response('Unauthorized', 401)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('Missing or invalid API key');

        $exchange->getRates('USD');
    }

    public function test_403_throws_pro_required_error(): void
    {
        Http::fake(['*/api/rates*' => Http::response('Forbidden', 403)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('Pro subscription');

        $exchange->getRates('USD');
    }

    public function test_404_throws_not_found_error(): void
    {
        Http::fake(['*/api/rates*' => Http::response('Not Found', 404)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('not found');

        $exchange->getRates('USD');
    }

    public function test_429_throws_rate_limit_error(): void
    {
        Http::fake(['*/api/rates*' => Http::response('Too Many Requests', 429)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $exchange->getRates('USD');
    }

    public function test_500_throws_generic_api_error(): void
    {
        Http::fake(['*/api/rates*' => Http::response('Internal Server Error', 500)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('API error (HTTP 500)');

        $exchange->getRates('USD');
    }

    public function test_invalid_json_throws(): void
    {
        Http::fake(['*/api/rates*' => Http::response('not json', 200)]);
        $exchange = app(UniRateExchange::class);

        $this->expectException(UniRateException::class);
        $this->expectExceptionMessage('Invalid API response');

        $exchange->getRates('USD');
    }
}
