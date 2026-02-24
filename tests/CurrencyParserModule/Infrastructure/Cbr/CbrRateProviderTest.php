<?php

namespace App\Tests\RateProvider\Infrastructure\Cbr;

use App\RateProvider\Infrastructure\Providers\CbrRateProvider\CbrRateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CbrRateProviderTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private CbrRateProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->provider = new CbrRateProvider(
            $this->httpClient,
            'https://cbr.ru/scripts/XML_daily.asp',
            ['USD', 'EUR'],
            30,
            'RUB'
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
                'query' => ['date_req' => '02/03/2020']
            ])
            ->willReturn($response);

        $date = new \DateTimeImmutable('2020-03-02');
        $rates = $this->provider->getRates($date);

        $this->assertCount(2, $rates); // Only USD and EUR (monitored currencies)
        $this->assertEquals('USD', $rates[0]->targetCurrency->code);
        $this->assertEquals(65.9436, $rates[0]->value);
        $this->assertEquals(1, $rates[0]->nominal);
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

        $date = new \DateTimeImmutable('2020-03-02');
        $rates = $this->provider->getRates($date);

        // AUD is not in default monitored currencies (USD, EUR, GBP, JPY, CNY)
        $this->assertCount(1, $rates);
        $this->assertEquals('USD', $rates[0]->targetCurrency->code);
    }

    /**
     * Test case 3: Throws exception on service unavailable
     */
    public function testThrowsExceptionOnServiceUnavailable(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Connection failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CBR service unavailable');

        $date = new \DateTimeImmutable('2020-03-02');
        $this->provider->getRates($date);
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

        $date = new \DateTimeImmutable('2020-03-02');
        $rates = $this->provider->getRates($date);

        $this->assertEquals(74.0247, $rates[0]->value);
    }
}

