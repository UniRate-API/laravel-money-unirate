<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class UniRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/unirate.php', 'unirate');

        $this->app->singleton(UniRateExchange::class, function ($app) {
            $config = $app['config']['unirate'];

            return new UniRateExchange(
                apiKey: $config['api_key'] ?? '',
                baseUrl: $config['base_url'] ?? 'https://api.unirateapi.com',
                baseCurrency: $config['base_currency'] ?? 'USD',
                timeout: (int) ($config['timeout'] ?? 10),
                httpFactory: $app->make(HttpFactory::class),
                cache: $app->make(CacheManager::class),
                cacheStore: $config['cache']['store'] ?? null,
                cacheTtl: (int) ($config['cache']['ttl'] ?? 3600),
                cachePrefix: $config['cache']['prefix'] ?? 'unirate',
            );
        });

        $this->app->alias(UniRateExchange::class, 'unirate');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/unirate.php' => config_path('unirate.php'),
            ], 'unirate-config');

            $this->commands([
                Console\UpdateRatesCommand::class,
            ]);
        }

        Money::macro('convertVia', function (string|Currency $to, int $roundingMode = Money::ROUND_HALF_UP): Money {
            /** @var Money $this */
            return app(UniRateExchange::class)->convert($this, $to, $roundingMode);
        });
    }

    public function provides(): array
    {
        return [UniRateExchange::class, 'unirate'];
    }
}
