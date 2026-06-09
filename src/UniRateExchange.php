<?php

declare(strict_types=1);

namespace UniRate\LaravelMoney;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

final class UniRateExchange
{
    public const VERSION = '0.1.0';

    private readonly PendingRequest $http;
    private readonly ?string $cacheStore;
    private readonly int $cacheTtl;
    private readonly string $cachePrefix;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $baseCurrency,
        int $timeout,
        HttpFactory $httpFactory,
        private readonly CacheManager $cache,
        ?string $cacheStore = null,
        int $cacheTtl = 3600,
        string $cachePrefix = 'unirate',
    ) {
        $this->http = $httpFactory
            ->baseUrl(rtrim($baseUrl, '/'))
            ->timeout($timeout)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'laravel-money-unirate/' . self::VERSION,
            ]);
        $this->cacheStore = $cacheStore;
        $this->cacheTtl = $cacheTtl;
        $this->cachePrefix = $cachePrefix;
    }

    /**
     * Get the exchange rate between two currencies.
     */
    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $rates = $this->getRates($from);

        if (!isset($rates[$to])) {
            throw new UniRateException("Currency pair {$from}/{$to} not available.");
        }

        return $rates[$to];
    }

    /**
     * Get all rates for a base currency (cached).
     *
     * @return array<string, float>
     */
    public function getRates(string $base = null): array
    {
        $base = strtoupper($base ?? $this->baseCurrency);
        $cacheKey = "{$this->cachePrefix}:rates:{$base}";

        if ($this->cacheTtl > 0) {
            $cached = $this->cacheRepository()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->request('/api/rates', ['from' => $base]);
        $rates = [];
        foreach (($response['rates'] ?? []) as $code => $value) {
            $rates[(string) $code] = (float) $value;
        }

        if ($this->cacheTtl > 0) {
            $this->cacheRepository()->put($cacheKey, $rates, $this->cacheTtl);
        }

        return $rates;
    }

    /**
     * Convert a Money instance to another currency using live UniRate rates.
     */
    public function convert(Money $money, string|Currency $to, int $roundingMode = Money::ROUND_HALF_UP): Money
    {
        $toCurrency = $to instanceof Currency ? $to : new Currency($to);
        $fromCode = $money->getCurrency()->getCurrency();
        $toCode = $toCurrency->getCurrency();
        $rate = $this->getRate($fromCode, $toCode);

        return $money->convert($toCurrency, $rate, $roundingMode);
    }

    /**
     * Convert a raw amount between currency codes.
     */
    public function convertAmount(float $amount, string $from, string $to): float
    {
        return $amount * $this->getRate($from, $to);
    }

    /**
     * List all supported currency codes.
     *
     * @return list<string>
     */
    public function currencies(): array
    {
        $cacheKey = "{$this->cachePrefix}:currencies";

        if ($this->cacheTtl > 0) {
            $cached = $this->cacheRepository()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->request('/api/currencies', []);
        $currencies = [];
        foreach (($response['currencies'] ?? []) as $code) {
            $currencies[] = (string) $code;
        }

        if ($this->cacheTtl > 0) {
            $this->cacheRepository()->put($cacheKey, $currencies, $this->cacheTtl);
        }

        return $currencies;
    }

    /**
     * Flush all cached UniRate data.
     */
    public function flushCache(): void
    {
        $repo = $this->cacheRepository();

        if (method_exists($repo->getStore(), 'flush')) {
            // For stores that support tagging or prefix flushing we do our best;
            // for file/database/array stores we just forget known keys.
        }

        $repo->forget("{$this->cachePrefix}:currencies");
        // Rate keys are base-specific; forget the common one.
        $repo->forget("{$this->cachePrefix}:rates:" . strtoupper($this->baseCurrency));
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $path, array $query): array
    {
        $query['api_key'] = $this->apiKey;

        /** @var Response $response */
        $response = $this->http->get($path, $query);

        if ($response->successful()) {
            $data = $response->json();
            if (!is_array($data)) {
                throw new UniRateException(
                    'Invalid API response: expected JSON object, got: ' . substr((string) $response->body(), 0, 200),
                );
            }
            return $data;
        }

        $status = $response->status();
        $body = $response->body();

        throw match (true) {
            $status === 401 => new UniRateException('Missing or invalid API key.', $status),
            $status === 403 => new UniRateException('Endpoint requires a Pro subscription.', $status),
            $status === 404 => new UniRateException('Currency not found or endpoint unavailable.', $status),
            $status === 429 => new UniRateException('Rate limit exceeded.', $status),
            default => new UniRateException("API error (HTTP {$status}): {$body}", $status),
        };
    }

    private function cacheRepository(): \Illuminate\Contracts\Cache\Repository
    {
        return $this->cache->store($this->cacheStore);
    }
}
