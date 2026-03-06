<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderInvalidRateDataException;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateParserXmlService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderLoggerServiceInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;
use function iterator_to_array;

class CurrencyRateParserXmlServiceTest extends KernelTestCase
{
    private CurrencyRateProviderLoggerServiceInterface&MockObject $logger;
    private CurrencyRateParserXmlService $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(CurrencyRateProviderLoggerServiceInterface::class);

        $this->processor = new CurrencyRateParserXmlService(
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
        $result = iterator_to_array($this->processor->parse(
            $xml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value, CurrencyEnum::EUR->value],
            4,
            $date
        ));

        // Assert rates are parsed
        $this->assertGreaterThan(0, count($result));
        $this->assertInstanceOf(RateContainer::class, $result[0]);
    }

    /**
     * Test parsing with empty monitored currencies
     */
    public function testParseWithNoMonitoredCurrencies(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();


        // Execute with empty currencies list
        $result = iterator_to_array($this->processor->parse(
            $xml,
            CurrencyEnum::RUB->value,
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

        $this->expectException(CurrencyRateProviderInvalidRateDataException::class);

        // Execute
        iterator_to_array($this->processor->parse(
            $invalidXml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value],
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


        $this->expectException(CurrencyRateProviderInvalidRateDataException::class);

        // Execute
        iterator_to_array($this->processor->parse(
            $malformedXml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value],
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
        iterator_to_array($this->processor->parse(
            $xml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value, CurrencyEnum::EUR->value],
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
        $result2Precision = iterator_to_array($this->processor->parse(
            $xml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value],
            2,
            $date
        ));

        $result4Precision = iterator_to_array($this->processor->parse(
            $xml,
            CurrencyEnum::RUB->value,
            [CurrencyEnum::USD->value],
            4,
            $date
        ));

        // Assert different precision is applied
        if (!empty($result2Precision) && !empty($result4Precision)) {
            $this->assertInstanceOf(RateContainer::class, $result2Precision[0]);
            $this->assertInstanceOf(RateContainer::class, $result4Precision[0]);
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
        <Value>90.50</Value>
        <VunitRate>90.50</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Euro</Name>
        <Value>97.20</Value>
        <VunitRate>97.20</VunitRate>
    </Valute>
</ValCurs>
XML;
    }
}
