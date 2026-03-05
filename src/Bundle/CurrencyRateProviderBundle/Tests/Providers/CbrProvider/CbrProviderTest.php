<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Tests\Providers\CbrProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\CurrencyRateProviderBundleException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\ProviderConfigurationException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\ProviderException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLoggerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\CbrProvider;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\RateProcessorContract;
use App\Domain\Enum\CurrencyEnum;
use DateTimeImmutable;
use Exception;
use Generator;
use Money\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CbrProviderTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private CurrencyRateProviderLoggerContract&MockObject $logger;
    private RateProcessorContract&MockObject $rateProcessor;
    private CbrProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(CurrencyRateProviderLoggerContract::class);
        $this->rateProcessor = $this->createMock(RateProcessorContract::class);

        $this->provider = new CbrProvider(
            $this->httpClient,
            $this->logger,
            $this->rateProcessor,
            'https://cbr.ru/scripts/XML_daily.asp',
            ['USD', 'EUR'],
            'RUB',
            30,
            4
        );
    }

    /**
     * Test successful rate retrieval
     */
    public function testGetRatesSuccessfully(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xmlContent = $this->getSampleXmlContent();

        // Mock HTTP response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($xmlContent);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://cbr.ru/scripts/XML_daily.asp', [
                'query' => ['date_req' => '02/03/2026'],
                'timeout' => 30,
                'headers' => ['Accept' => 'text/xml'],
            ])
            ->willReturn($response);

        // Mock processor to return empty generator
        $this->rateProcessor
            ->expects($this->once())
            ->method('process')
            ->willReturn($this->createGeneratorFromRates([]));

        // Execute - must iterate generator to trigger HTTP request
        $result = iterator_to_array($this->provider->getRates($date));

        // Assert it returns array
        $this->assertIsArray($result);
    }

    /**
     * Test handling of 4xx client errors (non-retryable)
     */
    public function testHandles4xxClientError(): void
    {
        $date = new DateTimeImmutable('2026-03-02');

        // Create inline exception implementation
        $exception = new class extends Exception implements ClientExceptionInterface {
            public function getResponse(): ResponseInterface {
                $resp = new class implements ResponseInterface {
                    public function getStatusCode(): int { return 404; }
                    public function getHeaders(bool $throw = true): array { return []; }
                    public function getContent(bool $throw = true): string { return ''; }
                    public function toArray(bool $throw = true): array { return []; }
                    public function cancel(): void { }
                    public function getInfo(?string $type = null): mixed { return null; }
                };
                return $resp;
            }
        };
        $exception->__construct('Not Found');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        // Assert exception is thrown
        $this->expectException(ProviderConfigurationException::class);
        $this->expectExceptionMessageMatches('/HTTP 404/');

        // Execute
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test handling of 3xx redirect errors (non-retryable)
     */
    public function testHandles3xxRedirectError(): void
    {
        $date = new DateTimeImmutable('2026-03-02');

        // Create inline exception implementation
        $exception = new class extends Exception implements RedirectionExceptionInterface {
            public function getResponse(): ResponseInterface {
                $resp = new class implements ResponseInterface {
                    public function getStatusCode(): int { return 301; }
                    public function getHeaders(bool $throw = true): array { return []; }
                    public function getContent(bool $throw = true): string { return ''; }
                    public function toArray(bool $throw = true): array { return []; }
                    public function cancel(): void { }
                    public function getInfo(?string $type = null): mixed { return null; }
                };
                return $resp;
            }
        };
        $exception->__construct('Moved Permanently');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        // Assert exception is thrown
        $this->expectException(ProviderConfigurationException::class);

        // Execute
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test handling of 5xx server errors (retriable)
     */
    public function testHandles5xxServerError(): void
    {
        $date = new DateTimeImmutable('2026-03-02');

        // Create inline exception implementation
        $exception = new class extends Exception implements ServerExceptionInterface {
            public function getResponse(): ResponseInterface {
                $resp = new class implements ResponseInterface {
                    public function getStatusCode(): int { return 503; }
                    public function getHeaders(bool $throw = true): array { return []; }
                    public function getContent(bool $throw = true): string { return ''; }
                    public function toArray(bool $throw = true): array { return []; }
                    public function cancel(): void { }
                    public function getInfo(?string $type = null): mixed { return null; }
                };
                return $resp;
            }
        };
        $exception->__construct('Service Unavailable');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        // Assert ProviderException (retriable) is thrown
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessageMatches('/retriable/');

        // Execute
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test handling of network/transport errors (retriable)
     */
    public function testHandlesNetworkError(): void
    {
        $date = new DateTimeImmutable('2026-03-02');

        // Use actual exception class
        $exception = new Exception('Connection timeout');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        // Plain exceptions are unexpected, so wrapped as CurrencyRateProviderBundleException
        $this->expectException(CurrencyRateProviderBundleException::class);
        $this->expectExceptionMessageMatches('/Unexpected error/');

        // Execute
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test logging is called for successful request
     */
    public function testLogsSuccessfulRequest(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xmlContent = $this->getSampleXmlContent();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($xmlContent);

        $this->httpClient->method('request')->willReturn($response);
        $this->rateProcessor->method('process')->willReturn($this->createGeneratorFromRates([]));

        // Assert logger is called
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        // Execute
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test logging is called for error
     */
    public function testLogsErrorRequest(): void
    {
        $date = new DateTimeImmutable('2026-03-02');

        $exception = new class extends Exception implements ClientExceptionInterface {
            public function getResponse(): ResponseInterface {
                $resp = new class implements ResponseInterface {
                    public function getStatusCode(): int { return 404; }
                    public function getHeaders(bool $throw = true): array { return []; }
                    public function getContent(bool $throw = true): string { return ''; }
                    public function toArray(bool $throw = true): array { return []; }
                    public function cancel(): void { }
                    public function getInfo(?string $type = null): mixed { return null; }
                };
                return $resp;
            }
        };
        $exception->__construct('Not Found');

        $this->httpClient->method('request')->willThrowException($exception);

        // Assert logger is called with error (at least once for 404)
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('error');

        try {
            iterator_to_array($this->provider->getRates($date));
        } catch (ProviderConfigurationException) {
            // Expected
        }
    }

    /**
     * Test rate processing with multiple rates
     */
    public function testProcessesMultipleRates(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xmlContent = $this->getSampleXmlContent();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($xmlContent);

        $this->httpClient->method('request')->willReturn($response);

        // Create rates with proper currency objects
        $rub = new Currency(CurrencyEnum::RUB->value);
        $usd = new Currency(CurrencyEnum::USD->value);
        $eur = new Currency(CurrencyEnum::EUR->value);

        $rates = [
            new Rate($rub, $usd, '90.5', $date),
            new Rate($usd, $rub, '0.01105', $date),
            new Rate($rub, $eur, '97.2', $date),
            new Rate($eur, $rub, '0.01029', $date),
        ];

        $this->rateProcessor->method('process')->willReturn($this->createGeneratorFromRates($rates));

        // Execute - collect all yielded arrays
        $result = iterator_to_array($this->provider->getRates($date));

        // Assert we got arrays of rates
        $this->assertIsArray($result);
        // Each element is an array of Rate objects
        foreach ($result as $chunk) {
            $this->assertIsArray($chunk);
            foreach ($chunk as $rate) {
                $this->assertInstanceOf(Rate::class, $rate);
            }
        }
    }

    /**
     * Helper method to create a generator from rates
     */
    private function createGeneratorFromRates(array $rates): Generator
    {
        foreach ($rates as $rate) {
            yield $rate;
        }
    }

    /**
     * Helper method to get sample XML content
     */
    private function getSampleXmlContent(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs Date="02.03.2026" name="Foreign Currency Market Extracts">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>US Dollar</Name>
        <Value>90,50</Value>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Euro</Name>
        <Value>97,20</Value>
    </Valute>
</ValCurs>
XML;
    }
}
