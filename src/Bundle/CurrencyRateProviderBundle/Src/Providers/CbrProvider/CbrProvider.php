<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\RateProcessorInterface;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use DateTimeInterface;
use Generator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
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
        private readonly array $monitoredCurrencies,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.timeout')]
        private readonly int $timeout = 30,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.base_currency')]
        private readonly string $baseCurrencyCode,

        #[Autowire(param: 'currency_rate_provider.cbr_provider.rate_precision')]
        private readonly int $ratePrecision = 16,
    ) {
    }

    /**
     * @return Generator<int, array<Rate>>
     * @throws RuntimeException
     */
    public function getRates(DateTimeInterface $date, int $chunkSize = 1000): Generator
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiUrl, [
                    'query' => ['date_req' => $date->format('d/m/Y')],
                    'timeout' => $this->timeout,
                ]
            );

            $chunk = [];
            foreach ($this->rateProcessor->process(
                $response->getContent(),
                $this->baseCurrencyCode,
                $this->monitoredCurrencies,
                $this->ratePrecision,
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
        } catch (HttpExceptionInterface $e) {
            // TODO: Log
            throw new RuntimeException('CBR HTTP error', 0, $e);
        } catch (Throwable $e) {
            // TODO: Log
            throw new RuntimeException('CBR service unavailable', 0, $e);
        }
    }
}

