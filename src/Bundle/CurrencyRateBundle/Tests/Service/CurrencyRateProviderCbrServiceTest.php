<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\ParserException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderException;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderCbrService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateParserServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\BundleLoggerServiceInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Exception;
use Generator;
use Money\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function iterator_to_array;

class CurrencyRateProviderCbrServiceTest extends TestCase
{
    use CurrencyTrait;

    private HttpClientInterface&MockObject $httpClient;
    private BundleLoggerServiceInterface&MockObject $logger;
    private CurrencyRateParserServiceInterface&MockObject $parser;
    private CurrencyRateProviderCbrService $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(BundleLoggerServiceInterface::class);
        $this->parser = $this->createMock(CurrencyRateParserServiceInterface::class);
        $this->provider = new CurrencyRateProviderCbrService(
            $this->httpClient,
            $this->logger,
            $this->parser,
            'https://cbr.ru/scripts/XML_daily.asp',
            30,
            [self::getUsd()->getCode(), self::getEur()->getCode()],
            self::getRub()->getCode(),
            4
        );
    }

    /**
     * Test successful rate retrieval
     */
    public function testInvalidCurrencyCode(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Base currency code is not configured');
        iterator_to_array(new CurrencyRateProviderCbrService(
            $this->httpClient,
            $this->logger,
            $this->parser,
            'https://cbr.ru/scripts/XML_daily.asp',
            30,
            [self::getUsd()->getCode(), self::getEur()->getCode()],
            '',
            4
        )->getRates($date));
    }

    /**
     * Test successful rate retrieval
     */
    public function testGetRatesSuccessfully(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xmlContent = $this->getSampleXmlContent();
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($xmlContent);
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://cbr.ru/scripts/XML_daily.asp', [
                'query' => ['date_req' => '02/03/2026'],
                'timeout' => 4,
                'headers' => ['Accept' => 'text/xml'],
            ])
            ->willReturn($response);
        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($this->createGeneratorFromRates([]));
        $result = iterator_to_array($this->provider->getRates($date));
        $this->assertIsArray($result);
    }

    /**
     * Test handling of HTTP errors
     */
    public function testHandlesHttpError(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $exception = new class extends Exception implements ExceptionInterface {
        };
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);
        $this->expectException(ProviderException::class);
        iterator_to_array($this->provider->getRates($date));
    }

    /**
     * Test handling of ParserException
     */
    public function testHandlesParserException(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $exception = new ParserException('parser failed');
        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->willThrowException($exception);

        $this->expectException(ProviderException::class);
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
        $this->parser->method('parse')->willReturn($this->createGeneratorFromRates([]));
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');
        iterator_to_array($this->provider->getRates($date));
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
        $rub = new Currency(self::getRub()->getCode());
        $usd = new Currency(self::getUsd()->getCode());
        $eur = new Currency(self::getEur()->getCode());
        $rates = [
            new RateContainer($rub, $usd, BigDecimal::of('90.5'), $date),
            new RateContainer($usd, $rub, BigDecimal::of('0.01105'), $date),
            new RateContainer($rub, $eur, BigDecimal::of('97.2'), $date),
            new RateContainer($eur, $rub, BigDecimal::of('0.01029'), $date),
        ];
        $this->parser->method('parse')->willReturn($this->createGeneratorFromRates($rates));
        $result = iterator_to_array($this->provider->getRates($date));
        $this->assertIsArray($result);
        foreach ($result as $chunk) {
            $this->assertIsArray($chunk);
            foreach ($chunk as $rate) {
                $this->assertInstanceOf(RateContainer::class, $rate);
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
        <VunitRate>90,50</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Euro</Name>
        <Value>97,20</Value>
        <VunitRate>97,20</VunitRate>
    </Valute>
</ValCurs>
XML;
    }
}
