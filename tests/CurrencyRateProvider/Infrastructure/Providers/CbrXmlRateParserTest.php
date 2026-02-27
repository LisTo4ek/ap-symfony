<?php

declare(strict_types=1);

namespace App\Tests\CurrencyRateProvider\Infrastructure\Providers;

use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use App\Tests\KernelTestCase;
use App\Tests\Trait\CurrencyTrait;
use Exception;

class CbrXmlRateParserTest extends KernelTestCase
{
    use CurrencyTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initCurrencies();
    }

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
        <VunitRate>65,9436</VunitRate>
    </Valute>
    <Valute>
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Value>43,2533</Value>
        <VunitRate>43,2533</VunitRate>
    </Valute>
</ValCurs>
XML;

        $parser = $this->fromContainer(XmlProcessor::class);
        $rates = iterator_to_array(
            $parser->process($xmlContent, $this->rubCurrency->getCode(), [$this->usdCurrency->getCode()], 32)
        );

        $this->assertCount(1, $rates);
        $this->assertSame($this->usdCurrency->getCode(), $rates[0]->targetCurrency->getCode());
        $this->assertSame($this->rubCurrency->getCode(), $rates[0]->baseCurrency->getCode());
    }
}
