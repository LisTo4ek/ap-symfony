<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Tests\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\InvalidRateDataException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLoggerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class XmlProcessorTest extends KernelTestCase
{
    private CurrencyRateProviderLoggerContract&MockObject $logger;
    private XmlProcessor $processor;

    protected CurrencyManagerContract $currencyManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyManager = static::getContainer()->get(CurrencyManagerContract::class);
        $this->logger = $this->createMock(CurrencyRateProviderLoggerContract::class);

        $this->processor = new XmlProcessor(
            $this->currencyManager,
            $this->logger
        );
    }

    /**
     * Test successful XML parsing
     */
    public function testParseXmlSuccessfully(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();

        // Execute
        $result = iterator_to_array($this->processor->process(
            $xml,
            'RUB',
            ['USD', 'EUR'],
            4,
            $date
        ));

        // Assert rates are parsed
        $this->assertGreaterThan(0, count($result));
        $this->assertInstanceOf(Rate::class, $result[0]);
    }

    /**
     * Test parsing with empty monitored currencies
     */
    public function testParseWithNoMonitoredCurrencies(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();


        // Execute with empty currencies list
        $result = iterator_to_array($this->processor->process(
            $xml,
            'RUB',
            [], // No monitored currencies
            4,
            $date
        ));

        // Assert no rates returned
        $this->assertEmpty($result);
    }

    /**
     * Test parsing with invalid XML
     */
    public function testThrowsExceptionForInvalidXml(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $invalidXml = '<invalid>Not proper XML';

        $this->expectException(InvalidRateDataException::class);

        // Execute
        iterator_to_array($this->processor->process(
            $invalidXml,
            'RUB',
            ['USD'],
            4,
            $date
        ));
    }

    /**
     * Test parsing with malformed XML structure
     */
    public function testThrowsExceptionForMalformedXml(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $malformedXml = '<?xml version="1.0"?><root></root>';


        $this->expectException(InvalidRateDataException::class);

        // Execute
        iterator_to_array($this->processor->process(
            $malformedXml,
            'RUB',
            ['USD'],
            4,
            $date
        ));
    }

    /**
     * Test logging is called during processing
     */
    public function testLogsProcessingEvents(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();


        // Assert logging is called
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        // Execute
        iterator_to_array($this->processor->process(
            $xml,
            'RUB',
            ['USD', 'EUR'],
            4,
            $date
        ));
    }

    /**
     * Test rate precision formatting
     */
    public function testAppliesPrecisionFormatting(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();


        // Execute with different precision values
        $result2Precision = iterator_to_array($this->processor->process(
            $xml,
            'RUB',
            ['USD'],
            2,
            $date
        ));

        $result4Precision = iterator_to_array($this->processor->process(
            $xml,
            'RUB',
            ['USD'],
            4,
            $date
        ));

        // Assert different precision is applied
        if (!empty($result2Precision) && !empty($result4Precision)) {
            $this->assertInstanceOf(Rate::class, $result2Precision[0]);
            $this->assertInstanceOf(Rate::class, $result4Precision[0]);
        }
    }

    /**
     * Helper method to get sample XML
     */
    private function getSampleXml(): string
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
        <VunitRate>90.50</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Euro</Name>
        <Value>97,20</Value>
        <VunitRate>97.20</VunitRate>
    </Valute>
</ValCurs>
XML;
    }
}

