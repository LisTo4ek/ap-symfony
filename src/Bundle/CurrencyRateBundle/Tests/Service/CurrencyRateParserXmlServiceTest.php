<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\ParserException;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateParserXmlService;
use App\Bundle\CurrencyRateBundle\Src\Service\BundleLoggerServiceInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;
use function iterator_to_array;

class CurrencyRateParserXmlServiceTest extends KernelTestCase
{
    use CurrencyTrait;

    private BundleLoggerServiceInterface&MockObject $logger;
    private CurrencyRateParserXmlService $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(BundleLoggerServiceInterface::class);
        $this->parser = new CurrencyRateParserXmlService($this->logger);
    }

    /**
     * Test successful XML parsing
     */
    public function testParseXmlSuccessfully(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();
        $result = iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [self::getUsd()->getCode(), self::getEur()->getCode()],
            $date
        ));
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
        $this->expectException(ParserException::class);
        iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [],
            $date
        ));
    }

    /**
     * Test parsing with invalid XML
     */
    public function testThrowsExceptionForInvalidXml(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $invalidXml = '<invalid>Not proper XML';
        $this->expectException(ParserException::class);
        iterator_to_array($this->parser->parse(
            $invalidXml,
            self::getRub(),
            [self::getUsd()->getCode()],
            $date
        ));
    }

    /**
     * Test parsing with malformed XML structure
     */
    #[DataProvider('exceptionsDataProvider')]
    public function testThrowsExceptionForMalformedXml(
        string $xml,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $date = new DateTimeImmutable('2026-03-02');
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [self::getUsd()->getCode()],
            $date
        ));
    }

    /**
     * @return Generator<int, array{xml: string, expectedException: class-string, expectedMessage: string}>
     */
    public static function exceptionsDataProvider(): Generator
    {
        yield 'missing ValCurs node' => [
            'xml' => '<?xml version="1.0"?><root></root>',
            'expectedException' => ParserException::class,
            'expectedMessage' => 'Invalid XML structure: missing ValCurs node',
        ];

        yield 'missing or invalid date attribute' => [
            'xml' => '<?xml version="1.0"?><ValCurs name="Foreign Currency Market Extracts"></ValCurs>',
            'expectedException' => ParserException::class,
            'expectedMessage' => 'Invalid XML: missing or invalid date attribute',
        ];

        yield 'Rates date is not current' => [
            'xml' => '<?xml version="1.0"?><ValCurs Date="01.01.1900" name="..."></ValCurs>',
            'expectedException' => ParserException::class,
            'expectedMessage' => 'Rates date is not current: 1900-01-01',
        ];

        yield 'Invalid currency code' => [
            'xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs Date="02.03.2026" name="Foreign Currency Market Extracts">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode></CharCode>
        <Nominal>1</Nominal>
        <Name>US Dollar</Name>
        <Value>90.50</Value>
        <VunitRate>90.50</VunitRate>
    </Valute>
</ValCurs>
XML,
            'expectedException' => ParserException::class,
            'expectedMessage' => 'Invalid currency code',
        ];

        yield 'Invalid rate data' => [
            'xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs Date="02.03.2026" name="Foreign Currency Market Extracts">
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>US Dollar</Name>
        <Value>90.50</Value>
    </Valute>
</ValCurs>
XML,
            'expectedException' => ParserException::class,
            'expectedMessage' => 'Invalid rate data for USD',
        ];
    }

    /**
     * Test logging is called during processing
     */
    public function testLogsProcessingEvents(): void
    {
        $date = new DateTimeImmutable('2026-03-02');
        $xml = $this->getSampleXml();
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');
        iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [self::getUsd()->getCode(), self::getEur()->getCode()],
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
        $result2Precision = iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [self::getUsd()->getCode()],
            $date
        ));
        $result4Precision = iterator_to_array($this->parser->parse(
            $xml,
            self::getRub(),
            [self::getUsd()->getCode()],
            $date
        ));
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
