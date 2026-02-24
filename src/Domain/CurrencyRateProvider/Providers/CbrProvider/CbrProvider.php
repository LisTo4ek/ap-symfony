<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Providers\CbrProvider;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Base\Entity\Rate;
use App\Domain\CurrencyRateProvider\Providers\CbrProvider\Parser\RateProcessorInterface;
use App\Domain\CurrencyRateProvider\Providers\CbrProvider\Parser\XmlProcessor;
use App\Domain\CurrencyRateProvider\Providers\CurrencyRateProviderInterface;
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
     * @param array<CurrencyEnum> $monitoredCurrencies List of currency codes to monitor from configuration
     * @param int $timeout Request timeout in seconds from configuration
     * @param CurrencyEnum $baseCurrency Base currency code from configuration (default: RUB)
     * @param string $contentEncoding encoding of the XML response (default: windows-1251)
     */
    public function __construct(
        private readonly HttpClientInterface    $httpClient,

        #[Autowire(service: XmlProcessor::class)]
        private readonly RateProcessorInterface $rateParser,

        #[Autowire(param: 'rate_providers.cbr_rate_provider.api_url')]
        private readonly string $apiUrl,

        #[Autowire(param: 'rate_providers.cbr_rate_provider.monitored_currencies')]
        private readonly array $monitoredCurrencies,

        #[Autowire(param: 'rate_providers.cbr_rate_provider.timeout')]
        private readonly int $timeout = 30,

        #[Autowire(param: 'rate_providers.cbr_rate_provider.base_currency')]
        private readonly CurrencyEnum $baseCurrency = CurrencyEnum::RUB,

        #[Autowire(param: 'rate_providers.cbr_rate_provider.encoding')]
        private readonly string $contentEncoding = 'windows-1251'
    ) {
    }

    /**
     * @return Generator<int, array<Rate>>
     * @throws RuntimeException
     */
    public function getRates(DateTimeInterface $date, int $chunkSize = 100): Generator
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
            foreach ($this->rateParser->process(
                $response->getContent(),
                $this->baseCurrency,
                $this->monitoredCurrencies,
                $this->contentEncoding,
            ) as $rate) {
                $chunk[] = $rate;

                if (\count($chunk) === $chunkSize) {
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
