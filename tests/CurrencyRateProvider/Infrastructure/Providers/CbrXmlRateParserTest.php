<?php

namespace App\Tests\CurrencyRateProvider\Infrastructure\Providers;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Providers\CbrProvider\Processor\XmlProcessor;
use Exception;
use PHPUnit\Framework\TestCase;

class CbrXmlRateParserTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testParseFiltersCurrencies(): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="windows-1251"?>
<ValCurs Date="02.03.2020">
    <Valute>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Value>65,9436</Value>
    </Valute>
    <Valute>
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Value>43,2533</Value>
    </Valute>
</ValCurs>
XML;

        $parser = new XmlProcessor();
        $rates = iterator_to_array($parser->process($xmlContent, CurrencyEnum::RUB, [CurrencyEnum::USD], 'windows-1251'));

        $this->assertCount(1, $rates);
        $this->assertSame(CurrencyEnum::USD, $rates[0]->targetCurrency);
        $this->assertSame(CurrencyEnum::RUB, $rates[0]->baseCurrency);
    }
}
