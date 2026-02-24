<?php

namespace App\RateProvider\Infrastructure\Providers\CbrRateProvider;

use App\RateProvider\Domain\Contract\RateProviderInterface;
use App\RateProvider\Domain\Entity\Rate;
use App\RateProvider\Domain\ValueObject\Currency;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(RateProviderInterface::class)]
class CbrRateProvider implements RateProviderInterface
{
    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $apiUrl CBR API URL from configuration
     * @param array<Currency> $monitoredCurrencies List of currency codes to monitor from configuration
     * @param int $timeout Request timeout in seconds from configuration
     * @param string $baseCurrency Base currency code from configuration (default: RUB)
     * @param string $xmlEncoding Base currency code from configuration (default: RUB)
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CbrXmlRateParser $rateParser,
        #[Autowire(param: 'rate_providers.cbr_rate_provider.api_url')]
        private readonly string $apiUrl,
        #[Autowire(param: 'rate_providers.cbr_rate_provider.monitored_currencies')]
        private readonly array $monitoredCurrencies,
        #[Autowire(param: 'rate_providers.cbr_rate_provider.timeout')]
        private readonly int $timeout = 30,
        #[Autowire(param: 'rate_providers.cbr_rate_provider.base_currency')]
        private readonly Currency $baseCurrency = Currency::RUB,
        #[Autowire(param: 'rate_providers.cbr_rate_provider.encoding')]
        private readonly string $xmlEncoding = 'windows-1251'
    ) {
    }

    /** @return Rate[] */
    public function getRates(\DateTimeInterface $date): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'query' => ['date_req' => $date->format('d/m/Y')],
                'timeout' => $this->timeout,
            ]);

            $rates = [];
            foreach ($this->rateParser->parse(
                $response->getContent(),
                $this->baseCurrency,
                $this->monitoredCurrencies,
                $this->xmlEncoding,
            ) as $rate) {
                $rates[] = $rate;
            }

            return $rates;
        } catch (HttpExceptionInterface $e) {
            // TODO: Log
            throw new \RuntimeException('CBR HTTP error', 0, $e);
        } catch (\Throwable $e) {
            // TODO: Log
            throw new \RuntimeException('CBR service unavailable', 0, $e);
        }
    }
}
