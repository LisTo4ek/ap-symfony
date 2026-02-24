<?php

namespace App\Tests\RateProvider\Infrastructure\Providers;

use App\RateProvider\Infrastructure\Providers\CbrRateProvider\CbrXmlRateParser;
use PHPUnit\Framework\TestCase;

class CbrXmlRateParserTest extends TestCase
{
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

        $parser = new CbrXmlRateParser();
        $rates = iterator_to_array($parser->parse($xmlContent, 'RUB', ['USD'], 'windows-1251'));

        $this->assertCount(1, $rates);
        $this->assertSame('USD', $rates[0]->targetCurrency->code);
        $this->assertSame('RUB', $rates[0]->baseCurrency->code);
    }
}
