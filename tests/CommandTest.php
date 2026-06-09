<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney\Tests;

use Illuminate\Support\Facades\Http;
use UniRate\LaravelMoney\UniRateException;

final class CommandTest extends TestCase
{
    public function test_command_fetches_and_displays_rates(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92', 'GBP' => '0.79']], 200),
        ]);

        $this->artisan('unirate:rates')
            ->expectsOutputToContain('Fetched 2 rates')
            ->assertExitCode(0);
    }

    public function test_command_accepts_base_option(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['USD' => '1.09']], 200),
        ]);

        $this->artisan('unirate:rates', ['--base' => 'EUR'])
            ->expectsOutputToContain('EUR')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'from=EUR'));
    }

    public function test_command_flush_option(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response(['rates' => ['EUR' => '0.92']], 200),
        ]);

        $this->artisan('unirate:rates', ['--flush' => true])
            ->expectsOutputToContain('Cache flushed')
            ->assertExitCode(0);
    }

    public function test_command_shows_error_on_api_failure(): void
    {
        Http::fake([
            '*/api/rates*' => Http::response('Unauthorized', 401),
        ]);

        $this->artisan('unirate:rates')
            ->expectsOutputToContain('UniRate API error')
            ->assertExitCode(1);
    }
}
