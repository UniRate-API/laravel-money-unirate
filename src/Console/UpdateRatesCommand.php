<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Console;

use Illuminate\Console\Command;
use UniRate\LaravelMoney\UniRateExchange;
use UniRate\LaravelMoney\UniRateException;

final class UpdateRatesCommand extends Command
{
    protected $signature = 'unirate:rates
        {--base= : Base currency (defaults to config value)}
        {--flush : Flush cached rates before fetching}';

    protected $description = 'Fetch and cache current exchange rates from UniRate';

    public function handle(UniRateExchange $exchange): int
    {
        if ($this->option('flush')) {
            $exchange->flushCache();
            $this->components->info('Cache flushed.');
        }

        $base = $this->option('base') ?? config('unirate.base_currency', 'USD');

        try {
            $rates = $exchange->getRates($base);
        } catch (UniRateException $e) {
            $this->components->error("UniRate API error: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Fetched %d rates for base %s (cached for %ds).',
            count($rates),
            strtoupper($base),
            config('unirate.cache.ttl', 3600),
        ));

        $rows = [];
        foreach ($rates as $code => $rate) {
            $rows[] = [$code, number_format($rate, 6)];
        }
        $this->table(['Currency', 'Rate'], $rows);

        return self::SUCCESS;
    }
}
