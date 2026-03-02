<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\CurrencyRateProviderBundleException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\ProviderConfigurationException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\ProviderException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLoggerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Helper\DurationCalculator;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\RateProcessorContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderContract;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsAlias(CurrencyRateProviderContract::class)]
class CbrProvider implements CurrencyRateProviderContract
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CurrencyRateProviderLoggerContract $logger,

        #[Autowire(service: XmlProcessor::class)]
        private readonly RateProcessorContract $rateProcessor,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.api_url')]
        private readonly string $apiUrl,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.monitored_currencies')]
        private readonly array $monitoredCurrencies = [],

        #[Autowire(param: 'currency_rate_provider.cbr_provider.base_currency')]
        private readonly string $baseCurrencyCode = 'RUB',

        #[Autowire(param: 'currency_rate_provider.cbr_provider.timeout')]
        private readonly int $timeout = 30,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.rate_precision')]
        private readonly int $ratePrecision = 16,
    ) {
        $this->logger->logProviderInit('CBR', [
            'api_url' => $this->apiUrl,
            'timeout' => $this->timeout,
            'monitored_currencies_count' => count($this->monitoredCurrencies),
        ]);
    }

    /**
     * @return Generator<int, array<Rate>>
     * @throws CurrencyRateProviderBundleException
     */
    public function getRates(DateTimeImmutable $date, int $chunkSize = 1000): Generator
    {
        try {
            $startTime = DurationCalculator::start();
            $chunk = [];

            // Request rates (RetryableHttpClient handles retries automatically)
            $content = $this->requestRates($date);

            if ($content === null) {
                throw new ProviderException('Failed to retrieve rates from CBR API - empty response');
            }

            $duration = DurationCalculator::elapsed($startTime);
            $this->logger->info('Retrieved rates from CBR', [
                'date' => $date->format('Y-m-d'),
                'duration_ms' => $duration,
                'content_size' => strlen($content),
            ]);

            // Process and yield rates
            foreach ($this->rateProcessor->process(
                $content,
                $this->baseCurrencyCode,
                $this->monitoredCurrencies,
                $this->ratePrecision,
                $date
            ) as $rate) {
                $chunk[] = $rate;
                $chunk[] = new Rate(
                    $rate->targetCurrency,
                    $rate->baseCurrency,
                    bcdiv("1", $rate->rate, $this->ratePrecision),
                    $rate->date
                );

                if (\count($chunk) >= $chunkSize) {
                    yield $chunk;
                    $chunk = [];
                }
            }

            if (!empty($chunk)) {
                yield $chunk;
            }

        } catch (CurrencyRateProviderBundleException $e) {
            throw $e;
        } catch (Throwable $e) {
            // Unexpected error - log and wrap
            $this->logger->error('Unexpected error in getRates', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            throw new CurrencyRateProviderBundleException(
                "Unexpected error: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Request rates from CBR API (single attempt)
     *
     * Handles various HTTP and network errors with appropriate logging and exception handling.
     * Note: Retries are NOT handled here - implement retry logic at a higher level if needed.
     *
     * @throws ProviderConfigurationException For non-retryable errors (4xx, 3xx)
     * @throws ProviderException For retriable errors (5xx, network)
     * @throws CurrencyRateProviderBundleException For unexpected errors
     */
    private function requestRates(DateTimeInterface $date): ?string
    {
        $startTime = DurationCalculator::start();

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
                'duration_ms' => DurationCalculator::elapsed($startTime),
                'content_size' => strlen($content),
                'date' => $date->format('d/m/Y'),
            ]);

            return $content;
        } catch (RedirectionExceptionInterface $e) {
            // 3xx - configuration issue, DO NOT RETRY
            $statusCode = $e->getResponse()->getStatusCode();
            $errorContext = $this->getErrorContext($startTime, $date, $e, $statusCode);

            $this->logger->error('HTTP redirect error - configuration issue', $errorContext);
            throw new ProviderConfigurationException(
                "CBR API returned HTTP {$statusCode} - verify API URL",
                0,
                $e
            );
        } catch (ClientExceptionInterface $e) {
            // 4xx - client or request issue, DO NOT RETRY
            $statusCode = $e->getResponse()->getStatusCode();
            $errorContext = $this->getErrorContext($startTime, $date, $e, $statusCode);

            $this->logger->error('HTTP client error', $errorContext);

            throw new ProviderConfigurationException(
                "CBR API returned HTTP {$statusCode} - check request configuration",
                0,
                $e
            );
        } catch (ServerExceptionInterface $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $errorContext = $this->getErrorContext($startTime, $date, $e, $statusCode);

            $this->logger->warning('HTTP server error', $errorContext);

            throw new ProviderException(
                "CBR API server error (HTTP {$statusCode}) - retriable",
                0,
                $e
            );

        } catch (TransportExceptionInterface $e) {
            $errorContext = $this->getErrorContext($startTime, $date, $e);
            $exceptionClass = get_class($e);
            if (\str_contains($exceptionClass, 'Timeout')) {
                $this->logger->warning('Network timeout error - slow or unresponsive server', $errorContext);
            } elseif (\str_contains($exceptionClass, 'Connect')) {
                $this->logger->warning('Network connection error - unable to reach server', $errorContext);
            } else {
                $this->logger->warning('Network transport error', $errorContext);
            }

            throw new ProviderException(
                "Network error - retriable",
                0,
                $e
            );

        } catch (HttpExceptionInterface $e) {
            $errorContext = $this->getErrorContext($startTime, $date, $e);

            $this->logger->error('Generic HTTP exception', $errorContext);
            throw new ProviderException(
                "HTTP error: {$e->getMessage()}",
                0,
                $e
            );

        } catch (Throwable $e) {
            // Unexpected/unknown error
            $this->logger->error(
                'Unexpected error in requestRates',
                $this->getErrorContext($startTime, $date, $e)
            );
            throw new CurrencyRateProviderBundleException(
                "Unexpected error: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build error context array with common error information
     *
     * @param float $startTime Start time from DurationCalculator::start()
     * @param DateTimeInterface $date Date being requested
     * @param Throwable $e The exception that occurred
     * @return array<string, mixed> Error context for logging
     */
    private function getErrorContext(float $startTime, DateTimeInterface $date, Throwable $e, ?int $statusCode = null): array
    {
        return [
            'duration_ms' => DurationCalculator::elapsed($startTime),
            'error_class' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'date' => $date->format('d/m/Y'),
            'error' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'status_code' => $statusCode,
        ];
    }
}

