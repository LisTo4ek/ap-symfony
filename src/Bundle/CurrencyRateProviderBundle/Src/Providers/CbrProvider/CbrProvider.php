<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\FailedToGetRatesException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\RateProcessorInterface;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsAlias(CurrencyRateProviderInterface::class)]
class CbrProvider implements CurrencyRateProviderInterface
{
    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $apiUrl CBR API URL from configuration
     * @param array<string> $monitoredCurrencies List of currencies to monitor from configuration
     * @param int $timeout Request timeout in seconds from configuration
     * @param string $baseCurrencyCode Base currency code from configuration
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,

        #[Autowire(service: XmlProcessor::class)]
        private readonly RateProcessorInterface $rateProcessor,

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
    }

    /**
     * @return Generator<int, array<Rate>>
     * @throws RuntimeException
     */
    public function getRates(DateTimeImmutable $date, int $chunkSize = 1000): Generator
    {
        try {
            $chunk = [];
            $content = $this->requestRates($date);

            if ($content === null) {
                throw new FailedToGetRatesException('Failed to retrieve rates from CBR API');
            }

            foreach ($this->rateProcessor->process(
                $this->requestRates($date),
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
        } catch (FailedToGetRatesException $e) {
            // TODO: Log
        } catch (Throwable $e) {
            // TODO: Log
            throw new RuntimeException('CBR service unavailable', 0, $e);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function requestRates(DateTimeInterface $date): ?string
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiUrl, [
                    'query' => ['date_req' => $date->format('d/m/Y')],
                    'timeout' => $this->timeout,
                ]
            );

            return $response->getContent();
        } catch (HttpExceptionInterface $e) {
            // TODO: Log
        } catch (Throwable $e) {
            // TODO: Log
        }

        return null;
    }
}

