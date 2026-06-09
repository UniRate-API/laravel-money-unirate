<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Support\Facades\Http;

final class MoneyMacroTest extends TestCase
{
    public function test_convert_via_macro_exists(): void
    {
        $this->assertTrue(Money::hasMacro('convertVia'));
    }

    public function test_convert_via_returns_converted_money(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        $usd = new Money(1000, new Currency('USD'));
        $eur = $usd->convertVia('EUR');

        $this->assertInstanceOf(Money::class, $eur);
        $this->assertSame('EUR', $eur->getCurrency()->getCurrency());
    }

    public function test_convert_via_with_currency_object(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['GBP' => '0.79']], 200),
        ]);

        $usd = new Money(2500, new Currency('USD'));
        $gbp = $usd->convertVia(new Currency('GBP'));

        $this->assertSame('GBP', $gbp->getCurrency()->getCurrency());
    }

    public function test_convert_via_uses_cached_rates(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        $a = (new Money(1000, new Currency('USD')))->convertVia('EUR');
        $b = (new Money(2000, new Currency('USD')))->convertVia('EUR');

        Http::assertSentCount(1);
    }
}
