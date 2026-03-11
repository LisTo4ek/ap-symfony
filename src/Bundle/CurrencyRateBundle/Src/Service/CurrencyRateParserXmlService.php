<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\InvalidRateDataException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderConfigurationException;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Generator;
use Money\Currency;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

use function in_array;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function mb_strlen;

/**
 * XML parser for CBR currency rate responses.
 *
 * Parses the ValCurs XML structure returned by the Central Bank of Russia API,
 * validates the date and structure, and yields RateContainer objects for each
 * monitored currency found in the response.
 *
 * @property ProviderLoggerServiceInterface $logger Logger for XML processing diagnostics
 */
#[AsAlias(CurrencyRateParserServiceInterface::class)]
class CurrencyRateParserXmlService implements CurrencyRateParserServiceInterface
{
    public function __construct(
        private ProviderLoggerServiceInterface $logger,
    ) {
    }

    /**
     * Parses CBR XML content and yields RateContainer objects for monitored currencies.
     *
     * Validates the XML structure, checks that the response date matches the requested date,
     * filters currencies against the monitored list, and normalizes rate values.
     *
     * @param string $content Raw XML response body from the CBR API
     * @param string $baseCurrencyCode ISO 4217 base currency code (e.g. 'RUB')
     * @param array<string> $monitoredCurrencies List of ISO 4217 target currency codes to extract
     * @param DateTimeImmutable $date The expected date of the rates
     *
     * @return Generator<int, RateContainer> Yields a RateContainer for each monitored currency found
     *
     * @throws ProviderConfigurationException When monitored currencies list is empty
     * @throws InvalidRateDataException When XML is malformed, date mismatches, or data is invalid
     */
    public function parse(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        DateTimeImmutable $date,
    ): Generator {
        $this->logger->debug('Processing XML response', [
            'content_size' => mb_strlen($content),
            'date' => $date->format('Y-m-d'),
        ]);

        if (empty($monitoredCurrencies)) {
            throw new ProviderConfigurationException('Monitored currencies list is empty');
        }

        if (empty($baseCurrencyCode)) {
            throw new InvalidRateDataException('Invalid base currency code');
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($content);
            libxml_use_internal_errors(false);
            libxml_clear_errors();
        } catch (Throwable $e) {
            libxml_use_internal_errors(false);
            libxml_clear_errors();
            throw new InvalidRateDataException("Invalid XML: {$e->getMessage()}", 0, $e);
        }

        $containerNode = $this->resolveContainerNode($xml);
        if ($containerNode === null) {
            throw new InvalidRateDataException('Invalid XML structure: missing ValCurs node');
        }

        $rateDate = $this->parseRateDate($containerNode);
        if ($rateDate === null) {
            throw new InvalidRateDataException('Invalid XML: missing or invalid date attribute');
        }

        if (!DateCompareService::eq($rateDate, $date)) {
            throw new InvalidRateDataException(
                "Rates date is not current: {$rateDate->format('Y-m-d')}"
            );
        }

        $processedCount = 0;
        foreach ($containerNode->Valute as $currencyNode) {
            $targetCurrencyCode = (string) ($currencyNode->CharCode ?? '');
            if (empty($targetCurrencyCode)) {
                throw new InvalidRateDataException('Invalid currency code');
            }

            if (!in_array($targetCurrencyCode, $monitoredCurrencies, true)) {
                continue;
            }

            $rateValue = $this->processRate($targetCurrencyCode, $currencyNode);

            yield new RateContainer(
                new Currency($baseCurrencyCode),
                new Currency($targetCurrencyCode),
                $rateValue,
                $rateDate,
            );

            $processedCount++;
        }

        $this->logger->info('XML processing completed', [
            'date' => $date->format('Y-m-d'),
            'rates_processed' => $processedCount,
        ]);
    }

    /**
     * Extracts and parses the date from the ValCurs XML node's "Date" attribute (format: d.m.Y).
     *
     * @param SimpleXMLElement $valCurs The ValCurs root XML element
     *
     * @return DateTimeImmutable|null The parsed date, or null if missing or invalid
     */
    private function parseRateDate(SimpleXMLElement $valCurs): ?DateTimeImmutable
    {
        $dateValue = (string) ($valCurs['Date'] ?? '');
        if ($dateValue !== '') {
            $parsed = DateTimeImmutable::createFromFormat('d.m.Y', $dateValue);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Resolves the ValCurs container node from the parsed XML.
     *
     * Handles both cases where ValCurs is the root element or a child element.
     *
     * @param SimpleXMLElement $xml The root XML element
     *
     * @return SimpleXMLElement|null The ValCurs node, or null if not found
     */
    private function resolveContainerNode(SimpleXMLElement $xml): ?SimpleXMLElement
    {
        if ($xml->getName() === 'ValCurs') {
            return $xml;
        }

        if (isset($xml->ValCurs)) {
            return $xml->ValCurs;
        }

        return null;
    }

    /**
     * Extracts and normalizes the VunitRate value from a currency XML node.
     *
     * @param string $currencyCode The ISO 4217 currency code (for error messages)
     * @param SimpleXMLElement $node The Valute XML element containing rate data
     *
     * @return BigDecimal The normalized rate value
     *
     * @throws InvalidRateDataException If the VunitRate element is missing
     */
    private function processRate(string $currencyCode, SimpleXMLElement $node): BigDecimal
    {
        if (!$node->VunitRate) {
            throw new InvalidRateDataException(
                "Invalid rate data for {$currencyCode}: missing VunitRate"
            );
        }

        return NumberService::normalize((string) $node->VunitRate);
    }
}
