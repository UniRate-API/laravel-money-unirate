# Laravel Money UniRate

Exchange-rate bridge for [akaunting/laravel-money](https://github.com/akaunting/laravel-money) — automatic rate fetching, caching, and conversion powered by [UniRate API](https://unirateapi.com).

[![CI](https://github.com/UniRate-API/laravel-money-unirate/actions/workflows/ci.yml/badge.svg)](https://github.com/UniRate-API/laravel-money-unirate/actions/workflows/ci.yml)

## Installation

```bash
composer require unirate-api/laravel-money
```

The service provider and facade are auto-discovered. Publish the config if you need to customise:

```bash
php artisan vendor:publish --tag=unirate-config
```

Set your API key in `.env`:

```env
UNIRATE_API_KEY=your-key-here
```

Get a free API key at [unirateapi.com](https://unirateapi.com).

## Usage

### Facade

```php
use UniRate\LaravelMoney\Facades\UniRate;

// Get a single rate
$rate = UniRate::getRate('USD', 'EUR');  // 0.92

// Get all rates for a base currency
$rates = UniRate::getRates('USD');  // ['EUR' => 0.92, 'GBP' => 0.79, ...]

// Convert a raw amount
$eur = UniRate::convertAmount(100.0, 'USD', 'EUR');  // 92.0

// Convert a Money instance
use Akaunting\Money\Money;
use Akaunting\Money\Currency;

$price = new Money(1000, new Currency('USD'));
$converted = UniRate::convert($price, 'EUR');
// → Money(920, Currency('EUR'))

// List supported currencies
$currencies = UniRate::currencies();  // ['USD', 'EUR', 'GBP', ...]
```

### Money macro

A `convertVia` macro is registered on `Akaunting\Money\Money`:

```php
$price = new Money(2500, new Currency('USD'));
$gbp = $price->convertVia('GBP');
// → Money(1975, Currency('GBP'))
```

### Artisan command

```bash
# Fetch and display current rates
php artisan unirate:rates

# Specify a base currency
php artisan unirate:rates --base=EUR

# Flush cache before fetching
php artisan unirate:rates --flush
```

### Dependency injection

```php
use UniRate\LaravelMoney\UniRateExchange;

class PricingController extends Controller
{
    public function show(UniRateExchange $exchange)
    {
        $rate = $exchange->getRate('USD', 'EUR');
        // ...
    }
}
```

## Configuration

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `api_key` | `UNIRATE_API_KEY` | — | Your UniRate API key |
| `base_url` | `UNIRATE_API_BASE_URL` | `https://api.unirateapi.com` | API base URL |
| `base_currency` | `UNIRATE_BASE_CURRENCY` | `USD` | Default base currency |
| `cache.store` | `UNIRATE_CACHE_STORE` | `null` (default) | Laravel cache store name |
| `cache.ttl` | `UNIRATE_CACHE_TTL` | `3600` | Cache TTL in seconds (0 = disabled) |
| `cache.prefix` | — | `unirate` | Cache key prefix |
| `timeout` | `UNIRATE_TIMEOUT` | `10` | HTTP timeout in seconds |

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13
- [akaunting/laravel-money](https://github.com/akaunting/laravel-money) 5.x or 6.x

<!-- unirate-ecosystem-footer:start -->
## Other UniRate clients

UniRate ships official client libraries and framework integrations across the
ecosystem. The repos below are all maintained under the
[UniRate-API](https://github.com/UniRate-API) org.

- **Languages:** [Python](https://github.com/UniRate-API/unirate-api-python) · [Node.js / TypeScript](https://github.com/UniRate-API/unirate-api-nodejs) · [Go](https://github.com/UniRate-API/unirate-api-go) · [Rust](https://github.com/UniRate-API/unirate-api-rust) · [Java](https://github.com/UniRate-API/unirate-api-java) · [Ruby](https://github.com/UniRate-API/unirate-api-ruby) · [PHP](https://github.com/UniRate-API/unirate-api-php) · [.NET](https://github.com/UniRate-API/unirate-api-dotnet) · [Swift](https://github.com/UniRate-API/unirate-api-swift)
- **Web frameworks:** [Next.js](https://github.com/UniRate-API/next-unirate) · [SvelteKit](https://github.com/UniRate-API/sveltekit-unirate) · [NestJS](https://github.com/UniRate-API/nestjs-unirate) · [Django / Wagtail](https://github.com/UniRate-API/wagtail-unirate) · [FastAPI](https://github.com/UniRate-API/fastapi-unirate) · [Flask](https://github.com/UniRate-API/flask-unirate) · [React](https://github.com/UniRate-API/react-unirate) · [tRPC](https://github.com/UniRate-API/trpc-unirate)
- **Static-site generators:** [Astro](https://github.com/UniRate-API/astro-unirate) · [Eleventy](https://github.com/UniRate-API/eleventy-unirate) · [Hugo](https://github.com/UniRate-API/hugo-unirate)
- **Data / orchestration:** [Airflow](https://github.com/UniRate-API/airflow-provider-unirate) · [dbt](https://github.com/UniRate-API/dbt-unirate) · [LangChain](https://github.com/UniRate-API/langchain-unirate)
- **Workflow / no-code:** [n8n](https://github.com/UniRate-API/n8n-nodes-unirate) · [Google Sheets](https://github.com/UniRate-API/unirate-sheets) · [MCP server](https://github.com/UniRate-API/unirate-mcp)
- **Editors / tools:** [VS Code](https://github.com/UniRate-API/vscode-unirate) · [Obsidian](https://github.com/UniRate-API/obsidian-currency)
- **Specialty bridges:** [Laravel Money](https://github.com/UniRate-API/laravel-money-unirate) · [RubyMoney](https://github.com/UniRate-API/money-unirate-api) · [NodaMoney (.NET)](https://github.com/UniRate-API/UniRateApi.NodaMoney)

Get a free API key at [unirateapi.com](https://unirateapi.com).
<!-- unirate-ecosystem-footer:end -->

## License

MIT — see [LICENSE](LICENSE).
