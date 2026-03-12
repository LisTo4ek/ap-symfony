<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\ParserException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use function count;
use function mb_strlen;

/**
 * CBR (Central Bank of Russia) implementation of the currency rate provider.
 *
 * Fetches daily exchange rates from the CBR XML API, parses the response,
 * computes inverse rates, and yields results in configurable-size chunks.
 * Handles HTTP errors with detailed logging and appropriate exception types.
 *
 * @property HttpClientInterface $httpClient Symfony HTTP client for API requests
 * @property BundleLoggerServiceInterface $logger Bundle-specific logger
 * @property CurrencyRateParserServiceInterface $rateProcessor XML parser that converts API response to RateContainers
 * @property string $apiUrl CBR API endpoint URL
 * @property int $ratePrecision Decimal precision for inverse rate calculations
 * @property array<string> $monitoredCurrencies ISO 4217 currency codes to monitor
 * @property string $baseCurrencyCode ISO 4217 base currency code (default: RUB)
 * @property int $timeout HTTP request timeout in seconds
 */
#[AsAlias(CurrencyRateProviderServiceInterface::class)]
class CurrencyRateProviderCbrService implements CurrencyRateProviderServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly BundleLoggerServiceInterface $logger,
        #[Autowire(service: CurrencyRateParserXmlService::class)]
        private readonly CurrencyRateParserServiceInterface $rateProcessor,
        #[Autowire(param: 'currency_rate_provider.cbr_provider.api_url')]
        private readonly string $apiUrl,
        #[Autowire(param: 'currency_rate_provider.calculate_rate_precision')]
        private readonly int $ratePrecision,
        /** @var array<string> */
        #[Autowire(param: 'currency_rate_provider.cbr_provider.monitored_currencies')]
        private readonly array $monitoredCurrencies = [],
        #[Autowire(param: 'currency_rate_provider.cbr_provider.base_currency')]
        private readonly string $baseCurrencyCode = CurrencyEnum::RUB->value,
        #[Autowire(param: 'currency_rate_provider.cbr_provider.timeout')]
        private readonly int $timeout = 30,
    ) {
        $this->logger->info('Provider initialized', [
            'provider' => 'CBR',
            'config' => [
                'api_url' => $this->apiUrl,
                'timeout' => $this->timeout,
                'monitored_currencies_count' => count($this->monitoredCurrencies),
            ],
        ]);
    }

    /**
     * Fetches exchange rates from the CBR API for a specific date and yields them in chunks.
     *
     * For each parsed rate, also computes and includes the inverse rate.
     * Chunks are yielded as arrays of RateContainer objects.
     *
     * @param DateTimeImmutable $date The date to fetch rates for
     * @param int $chunkSize Maximum number of RateContainer items per yielded chunk
     *
     * @return Generator<int, array<RateContainer>> Generator yielding arrays of rate containers
     *
     * @throws ProviderException On any provider, parsing, or unexpected error
     */
    public function getRates(DateTimeImmutable $date, int $chunkSize = 1000): Generator
    {
        try {
            $startTime = DurationCalculatorService::start();
            $chunk = [];
            $content = $this->requestRates($date);
            $duration = DurationCalculatorService::elapsed($startTime);
            $this->logger->info('Retrieved rates from CBR', [
                'date' => $date->format('Y-m-d'),
                'duration_ms' => $duration,
                'content_size' => strlen($content),
            ]);
            foreach (
                $this->rateProcessor->parse(
                    $content,
                    $this->baseCurrencyCode,
                    $this->monitoredCurrencies,
                    $date
                ) as $rate
            ) {
                $chunk[] = $rate;
                $chunk[] = new RateContainer(
                    $rate->targetCurrency,
                    $rate->baseCurrency,
                    BigDecimal::of(1)->dividedBy($rate->rate, max(1, $this->ratePrecision), RoundingMode::Ceiling),
                    $rate->date
                );

                if (count($chunk) >= $chunkSize) {
                    yield $chunk;
                    $chunk = [];
                }
            }

            if (!empty($chunk)) {
                yield $chunk;
            }
        } catch (ParserException $e) {
            throw new ProviderException('Parser error', 0, $e);
        } catch (ProviderException $e) {
            throw $e;
        }
    }

    /**
     * Request rates from CBR API (single attempt)
     *
     * Handles various HTTP and network errors with appropriate logging and exception handling.
     * Note: Retries are NOT handled here - implement retry logic at a higher level if needed.
     * @throws ProviderException On any HTTP or network error during the request
     *
     * @todo Implement retry logic
     */
    private function requestRates(DateTimeInterface $date): string
    {
        try {
            $this->logger->debug('HTTP request starting', [
                'date' => $date->format('d/m/Y'),
                'url' => $this->apiUrl,
                'timeout' => $this->timeout,
            ]);

            $response = $this->httpClient->request(
                'GET',
                $this->apiUrl,
                [
                    'query' => ['date_req' => $date->format('d/m/Y')],
                    'timeout' => $this->timeout,
                    'headers' => ['Accept' => 'text/xml'],
                ]
            );
            $content = $response->getContent();
            $this->logger->debug('HTTP request successful', [
                'status_code' => $response->getStatusCode(),
                'content_size' => mb_strlen($content),
                'date' => $date->format('d/m/Y'),
            ]);

            return $content;
        } catch (ExceptionInterface $e) {
            throw new ProviderException("HTTP error", 0, $e);
        }
    }
}
