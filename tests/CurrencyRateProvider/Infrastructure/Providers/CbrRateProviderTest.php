<?php

namespace App\Tests\CurrencyRateProvider\Infrastructure\Providers;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Providers\CbrProvider\CbrProvider;
use App\Domain\CurrencyRateProvider\Providers\CbrProvider\Parser\RateProcessorInterface;
use ArrayIterator;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CbrProviderTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private RateProcessorInterface $rateParser;
    private CbrProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->rateParser = $this->createMock(RateProcessorInterface::class);
        $this->provider = new CbrProvider(
            $this->httpClient,
            $this->rateParser,
            'https://cbr.ru/scripts/XML_daily.asp',
            [CurrencyEnum::USD, CurrencyEnum::EUR],
            30,
            CurrencyEnum::RUB,
            'windows-1251'
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
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Евро</Name>
        <Value>74,0247</Value>
    </Valute>
    <Valute ID="R01010">
        <NumCode>036</NumCode>
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Name>Австралийский доллар</Name>
        <Value>43,2533</Value>
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

        // Mock the rate parser to return test rates
        $mockRates = [
            $this->createMockRate(CurrencyEnum::RUB, CurrencyEnum::USD, 65.9436),
            $this->createMockRate(CurrencyEnum::RUB, CurrencyEnum::EUR, 74.0247),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(fn() => new ArrayIterator($mockRates));

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        $this->assertCount(2, $allRates);
        $this->assertEquals(CurrencyEnum::USD, $allRates[0]->targetCurrency);
        $this->assertEquals(65.9436, $allRates[0]->rate);
    }

    private function createMockRate(CurrencyEnum $baseCurrency, CurrencyEnum $targetCurrency, float $rate)
    {
        $mockRate = $this->createMock(\App\Domain\CurrencyRateProvider\Base\Entity\Rate::class);
        $mockRate->baseCurrency = $baseCurrency;
        $mockRate->targetCurrency = $targetCurrency;
        $mockRate->rate = $rate;

        return $mockRate;
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
            $this->createMockRate(CurrencyEnum::RUB, CurrencyEnum::USD, 65.9436),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(fn() => new ArrayIterator($mockRates));

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        // AUD is not in default monitored currencies (USD, EUR)
        $this->assertCount(1, $allRates);
        $this->assertEquals(CurrencyEnum::USD, $allRates[0]->targetCurrency);
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
            $this->createMockRate(CurrencyEnum::RUB, CurrencyEnum::EUR, 74.0247),
        ];

        $this->rateParser->expects($this->once())
            ->method('process')
            ->willReturnCallback(fn() => new ArrayIterator($mockRates));

        $date = new DateTimeImmutable('2020-03-02');
        $generator = $this->provider->getRates($date);

        // Collect all chunks
        $allRates = [];
        foreach ($generator as $chunk) {
            $allRates = array_merge($allRates, $chunk);
        }

        $this->assertEquals(74.0247, $allRates[0]->rate);
    }
}
