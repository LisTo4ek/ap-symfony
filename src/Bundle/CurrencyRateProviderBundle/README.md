# CurrencyRateProviderBundle

A comprehensive Symfony bundle for managing currency rates with support for multiple providers (CBR - Central Bank of Russia), flexible currency handling, and complete validation.

## Features

✅ **Multiple Currency Rate Providers** - CBR (Central Bank of Russia) built-in, extensible for other providers  
✅ **Complete ISO 4217 Support** - 180+ currency constants pre-defined  
✅ **Type-Safe Currency System** - `CurrencyContract` for contracts, `CurrencyIso4217` implementation  
✅ **Generic Validators** - Work with any `CurrencyContract` implementation  
✅ **Doctrine DBAL Integration** - Custom `currency_iso_4217` type for entities  
✅ **Configuration-Driven** - YAML config for allowed currencies  
✅ **Generator-Based Rate Fetching** - Memory-efficient chunked rate retrieval  
✅ **Event-Driven Architecture** - Events for rate save operations  
✅ **Extensible Design** - Add custom currencies and providers outside the bundle  

## Installation

The bundle is pre-installed and registered in `config/bundles.php`:

```php
App\Bundle\CurrencyRateProviderBundle\CurrencyRateProviderBundle::class => ['all' => true],
```

## Configuration

Create or edit `config/packages/currency_rate_provider.yaml`:

```yaml
currency_rate_provider:
    cbr_provider:
        api_url: 'https://cbr.ru/scripts/XML_daily.asp'
        monitored_currencies:
            - USD
            - EUR
            - GBP
            - JPY
            - CNY
        timeout: 30
        retry_attempts: 3
        base_currency: RUB

when@test:
    currency_rate_provider:
        cbr_provider:
            monitored_currencies:
                - USD
                - EUR
            timeout: 5
            retry_attempts: 1

when@dev:
    currency_rate_provider:
        cbr_provider:
            monitored_currencies:
                - USD
                - EUR
                - IRR
            timeout: 60
            retry_attempts: 1
```

## Quick Start

### Using Currency Constants

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyIso4217\CurrencyIso4217Enum;

// Get currency instance
$usd = CurrencyIso4217Enum::of(CurrencyIso4217Enum::USD);
$eur = CurrencyIso4217Enum::of(CurrencyIso4217Enum::EUR);

// Get code
echo $usd->getCode(); // "USD"

// Create from string
$jpy = CurrencyIso4217Enum::of('JPY');
```

### In Function Signatures

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;

function calculateRate(CurrencyContract $base, CurrencyContract $target): float
{
    return /* ... */;
}
```

### In Doctrine Entities

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ExchangeRate
{
    #[ORM\Column(type: 'currency_iso_4217')]
    private CurrencyContract $baseCurrency;

    #[ORM\Column(type: 'currency_iso_4217')]
    private CurrencyContract $targetCurrency;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 5)]
    private string $rate;
}
```

### Fetching Rates

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use DateTimeImmutable;

class RateService
{
    public function __construct(
        private CurrencyRateProviderInterface $rateProvider,
    ) {}

    public function fetchRates(): void
    {
        $date = new DateTimeImmutable('2026-02-25');
        
        // Returns Generator<int, array<Rate>>
        foreach ($this->rateProvider->getRates($date, 100) as $chunk) {
            // Process array of Rate objects
            foreach ($chunk as $rate) {
                echo $rate->baseCurrency->getCode();
                echo $rate->targetCurrency->getCode();
                echo $rate->rate; // Decimal string
            }
        }
    }
}
```

## Validation

### Single Currency Validation

Validate that a string is a valid currency code:

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Validator\ValidCurrency;

class CurrencyRequest
{
    #[ValidCurrency]
    public string $currencyCode;

    // Or specify a custom currency class
    #[ValidCurrency(currencyClass: CustomCurrency::class)]
    public string $customCurrency;
}
```

### Array of Currencies Validation

Validate that all items in an array are valid currencies:

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Validator\ValidCurrencyArray;

class MultiCurrencyRequest
{
    #[ValidCurrencyArray]
    public array $currencies;

    // With whitelist
    #[ValidCurrencyArray(allowedOnly: true, allowed: ['USD', 'EUR', 'GBP'])]
    public array $allowedCurrencies;

    // With custom currency class
    #[ValidCurrencyArray(currencyClass: CryptoCurrency::class)]
    public array $cryptoCurrencies;
}
```

### Programmatic Validation

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Service\CurrencyValidator;

// Check if valid
if (CurrencyValidator::isValid('USD')) {
    // Valid
}

// Validate and get instance
try {
    $currency = CurrencyValidator::validate('USD');
} catch (\InvalidArgumentException $e) {
    // Invalid currency
}

// Validate array
try {
    $currencies = CurrencyValidator::validateArray(['USD', 'EUR']);
} catch (\InvalidArgumentException $e) {
    // Invalid currencies
}

// Get all valid codes
$codes = CurrencyValidator::getValidCodes(); // ['AED', 'AFN', ...]

// With custom currency class
$cryptoCodes = CurrencyValidator::getValidCodes(CryptoCurrency::class);
```

## Currency System

### CurrencyContract Contract

All currency implementations must implement this interface:

```php
interface CurrencyContract
{
    public function getCode(): string;
}
```

### CurrencyIso4217 Class

Built-in ISO 4217 currency implementation with 180+ constants:

```php
class CurrencyIso4217 implements CurrencyContract
{
    public const USD = 'USD'; // United States Dollar
    public const EUR = 'EUR'; // Euro
    public const GBP = 'GBP'; // British Pound Sterling
    // ... and 177 more
}
```

### Creating Custom Currencies

Extend the system with custom currencies:

```php
namespace App\Currency;

use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;

class CryptoCurrency implements CurrencyContract
{
    public const BTC = 'BTC';
    public const ETH = 'ETH';
    public const XRP = 'XRP';

    private static array $instances = [];

    private function __construct(public readonly string $code) {}

    public static function of(string $code): self
    {
        return self::$instances[$code] ??= new self($code);
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
```

## Services

### CurrencyRateProviderInterface

Main service for fetching currency rates:

```php
interface CurrencyRateProviderInterface
{
    /**
     * Get currency rates for a specific date as a generator, chunked
     *
     * @return Generator<int, array<Rate>>
     */
    public function getRates(DateTimeInterface $date, int $chunkSize = 1000): Generator;
}
```

### CurrencyValidator

Static service for currency validation:

```php
class CurrencyValidator
{
    // Get valid codes from any CurrencyContract implementation
    public static function getValidCodes(?string $currencyClass = null): array;
    
    // Check if code is valid
    public static function isValid(string $code, ?string $currencyClass = null): bool;
    
    // Validate and get instance
    public static function validate(string $code, string $currencyClass = CurrencyIso4217::class): CurrencyContract;
    
    // Validate array
    public static function validateArray(array $codes, ?array $allowedOnly = null, ?string $currencyClass = null): array;
    
    // Convert code to instance
    public static function fromCode(string $code, ?string $currencyClass = null): CurrencyContract;
    
    // Get codes from instances
    public static function getCodes(array $currencies): array;
}
```

### CurrencyConfigProcessor

Service for processing configuration:

```php
class CurrencyConfigProcessor
{
    // Convert array of string codes to CurrencyContract instances
    public function processCurrencyCodes(array $codes): array;
    
    // Get codes from currencies
    public function getCurrencyCodes($currencies): array;
    
    // Check if array contains valid currencies
    public function isValidCurrencyArray($currencies): bool;
}
```

### RateFetcher

Service for fetching rates with error handling:

```php
class RateFetcher
{
    // Fetch rates, throws exception on error
    public function fetchRates(DateTimeInterface $date): Generator;
    
    // Try to fetch rates, returns null on error
    public function tryFetchRates(DateTimeInterface $date): ?Generator;
}
```

### RateManager

Domain service for rate persistence and management:

```php
class RateManager
{
    // Save rates to history and dispatch events
    public function saveRatesToHistory(array $rates): void;
    
    // Get current rates from database
    public function getCurrentRates(): array;
    
    // Check if current rates table has data
    public function hasCurrentRates(): bool;
    
    // Get rate history for currency pair and date range
    public function getRateHistoryByTargetCurrency(
        CurrencyEnum $baseCurrency,
        ?CurrencyEnum $targetCurrency,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): array;
    
    // Query rate history by base currency
    public function getRateHistoryByCurrency(
        CurrencyEnum $baseCurrency,
        ?CurrencyEnum $targetCurrency,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): array;
}
```

## Available ISO 4217 Currencies

180+ currencies are pre-defined as constants. Common ones include:

**Major Currencies:**
- `AED`, `AUD`, `CAD`, `CHF`, `EUR`, `GBP`, `JPY`, `USD`, `RUB`, `CNY`, `INR`

**European:**
- `BGN`, `CZK`, `DKK`, `HUF`, `NOK`, `PLN`, `RON`, `SEK`, `TRY`, `UAH`

**Asian:**
- `BDT`, `HKD`, `IDR`, `KRW`, `MYR`, `PHP`, `SGD`, `THB`, `VND`

**Americas:**
- `ARS`, `BRL`, `CLP`, `COP`, `MXN`, `PEN`, `UYU`

**Middle East & Africa:**
- `AED`, `EGP`, `IRR`, `ILS`, `JOD`, `KWD`, `SAR`, `ZAR`

See `AVAILABLE_CURRENCIES.md` for the complete list.

## Events

### RateSavedEvent

Dispatched when rates are saved:

```php
use App\Event\RateSavedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyRateSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RateSavedEvent::class => 'onRateSaved',
        ];
    }

    public function onRateSaved(RateSavedEvent $event): void
    {
        $rate = $event->getRate();
        // Handle rate saved
    }
}
```

## Advanced Usage

### Using with Custom Currency Provider

Create a custom provider:

```php
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use DateTimeInterface;
use Generator;

#[AsAlias(CurrencyRateProviderInterface::class)]
class MyCustomProvider implements CurrencyRateProviderInterface
{
    public function getRates(DateTimeInterface $date, int $chunkSize = 1000): Generator
    {
        // Your implementation
    }
}
```

### Injecting Configuration Values

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MyService
{
    public function __construct(
        #[Autowire('%currency_rate_provider.cbr_provider.api_url%')]
        private string $apiUrl,
        
        #[Autowire('%currency_rate_provider.cbr_provider.monitored_currencies%')]
        private array $monitoredCurrencies,
    ) {}
}
```

## Architecture

The bundle follows clean domain-driven design:

```
Src/
├── Base/                          # Core value objects
│   └── Currency/
│       ├── CurrencyContract.php  # Contract
│       ├── CurrencyIso4217.php    # ISO 4217 implementation
│       └── CurrencyType.php       # Doctrine DBAL type
├── Providers/                     # Rate providers
│   ├── CurrencyRateProviderInterface.php
│   └── CbrProvider/
│       ├── CbrProvider.php
│       └── Processor/
├── Service/                       # Domain services
│   ├── CurrencyValidator.php
│   ├── CurrencyConfigProcessor.php
│   ├── RateFetcher.php
│   └── RateManager.php
└── Validator/                     # Validation constraints
    ├── ValidCurrency.php
    ├── ValidCurrencyValidator.php
    ├── ValidCurrencyArray.php
    └── ValidCurrencyArrayValidator.php
```

## Documentation

- `CURRENCY_SYSTEM.md` - Complete currency system documentation
- `AVAILABLE_CURRENCIES.md` - Reference of all 180+ ISO 4217 currencies
- `GENERIC_CURRENCY_SYSTEM.md` - How to extend with custom currencies

## Commands

### Import Currency Rates

```bash
php bin/console app:import:currency-rates:cbr
```

Imports exchange rates from CBR API.

## Requirements

- PHP 8.1+
- Symfony 6.0+
- Doctrine ORM 2.14+

## Support

For issues and feature requests, please refer to the project documentation or contact the development team.

## License

See LICENSE file in the project root.

