<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Facades;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Support\Facades\Facade;
use UniRate\LaravelMoney\UniRateExchange;

/**
 * @method static float getRate(string $from, string $to)
 * @method static array getRates(?string $base = null)
 * @method static Money convert(Money $money, string|Currency $to, int $roundingMode = Money::ROUND_HALF_UP)
 * @method static float convertAmount(float $amount, string $from, string $to)
 * @method static list<string> currencies()
 * @method static void flushCache()
 *
 * @see UniRateExchange
 */
final class UniRate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UniRateExchange::class;
    }
}
