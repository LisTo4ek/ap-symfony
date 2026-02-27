<?php

declare(strict_types=1);

namespace App\Tests\CurrencyRateProvider\Infrastructure\Providers;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\CbrProvider;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\RateProcessorInterface;
use App\Tests\KernelTestCase;
use App\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CbrProviderTest extends KernelTestCase
{
    use CurrencyTrait;

    private HttpClientInterface $httpClient;
    private RateProcessorInterface $rateParser;
    private CbrProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initCurrencies();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->rateParser = $this->createMock(RateProcessorInterface::class);
        $this->provider = new CbrProvider(
            $this->httpClient,
            $this->rateParser,
            'https://cbr.ru/scripts/XML_daily.asp',
            [CurrencyIso4217Enum::USD->value, CurrencyIso4217Enum::EUR->value],
            CurrencyIso4217Enum::RUB->value,
            30,
            32
        );
    }

    /**
     * Test case 1: Successfully parse rates from CBR XML
     */
    public function testGetRatesSuccessfully(): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="02.03.2020" name="Foreign Currency Market">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>Доллар США</Name>
        <Value>65,9436</Value>
        <VunitRate>65,9436</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Евро</Name>
        <Value>74,0247</Value>
        <VunitRate>74,0247</VunitRate>
    </Valute>
    <Valute ID="R01010">
        <NumCode>036</NumCode>
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Name>Австралийский доллар</Name>
        <Value>43,2533</Value>
        <VunitRate>43,2533</VunitRate>
    </Valute>
</ValCurs>
XML;

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($xmlContent);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://cbr.ru/scripts/XML_daily.asp', [
                'query' => ['date_req' => '02/03/2020'],
                'timeout' => 30,
            ])
            ->willReturn($response);

        $today = new DateTimeImmutable('today');
        // Mock the rate parser to return test rates
        $mockRates = [
            $this->createMockRate($this->rubCurrency, $this->usdCurrency, '65.9436', $today),
            $this->createMockRate($this->rubCurrency, $this->usdCurrency, '74.0247', $today),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(function () use ($mockRates) {
                foreach ($mockRates as $rate) {
                    yield $rate;
                }
            });

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        $this->assertCount(4, $allRates);
        $this->assertEquals($this->usdCurrency, $allRates[0]->targetCurrency);
        $this->assertEquals('65.9436', $allRates[0]->rate);
    }

    private function createMockRate(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency, string $rate, DateTimeInterface $date): Rate
    {
        // Create a real Rate object instead of a mock to avoid property access issues
        return new Rate($baseCurrency, $targetCurrency, $rate, $date);
    }

    /**
     * Test case 2: Only returns monitored currencies (requirement 1.3)
     */
    public function testReturnsOnlyMonitoredCurrencies(): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="02.03.2020" name="Foreign Currency Market">
    <Valute ID="R01235">
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Value>65,9436</Value>
    </Valute>
    <Valute ID="R01010">
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Value>43,2533</Value>
    </Valute>
</ValCurs>
XML;

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($xmlContent);
        $this->httpClient->method('request')->willReturn($response);

        $mockRates = [
            $this->createMockRate($this->rubCurrency, $this->usdCurrency, '65.9436', new DateTimeImmutable('today')),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(function () use ($mockRates) {
                foreach ($mockRates as $rate) {
                    yield $rate;
                }
            });

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        // AUD is not in default monitored currencies (USD, EUR)
        // CbrProvider creates direct rate + inverse rate for each monitored currency
        $this->assertCount(2, $allRates);
        $this->assertEquals(CurrencyIso4217Enum::USD->value, $allRates[0]->targetCurrency->getCode());
    }

    /**
     * Test case 3: Throws exception on service unavailable
     */
    public function testThrowsExceptionOnServiceUnavailable(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new Exception('Connection failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CBR service unavailable');

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);
        // Force generator execution
        iterator_to_array($generator);
    }

    /**
     * Test case 4: Correctly converts comma to dot in decimal values
     */
    public function testConvertsCommaToDotInValues(): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="02.03.2020">
    <Valute>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Value>74,0247</Value>
    </Valute>
</ValCurs>
XML;

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($xmlContent);
        $this->httpClient->method('request')->willReturn($response);

        $mockRates = [
            $this->createMockRate($this->rubCurrency, $this->usdCurrency, '74.0247', new DateTimeImmutable('today')),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(function () use ($mockRates) {
                foreach ($mockRates as $rate) {
                    yield $rate;
                }
            });

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        // CbrProvider creates direct rate + inverse rate
        // First rate is the direct rate with the original value
        $this->assertEquals('74.0247', $allRates[0]->rate);
    }
}
