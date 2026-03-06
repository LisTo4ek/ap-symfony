<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderInvalidRateDataException;
use App\Bundle\CurrencyRateBundle\Src\Helper\DateCompare;
use App\Bundle\CurrencyRateBundle\Src\Helper\NumberHelper;
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

#[AsAlias(CurrencyRateParserServiceInterface::class)]
class CurrencyRateParserXmlService implements CurrencyRateParserServiceInterface
{
    public function __construct(
        private CurrencyRateProviderLoggerServiceInterface $logger,
    ) {
    }

    /**
     * @param array<string> $monitoredCurrencies
     * @return Generator<int, RateContainer>
     * @throws CurrencyRateProviderInvalidRateDataException when XML processing fails
     */
    public function parse(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        int $ratePrecision,
        DateTimeImmutable $date,
    ): Generator {
        $this->logger->debug('Processing XML response', [
            'content_size' => mb_strlen($content),
            'date' => $date->format('Y-m-d'),
        ]);

        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($content);
            libxml_use_internal_errors(false);
            libxml_clear_errors();
        } catch (Throwable $e) {
            libxml_use_internal_errors(false);
            libxml_clear_errors();
            throw new CurrencyRateProviderInvalidRateDataException("Invalid XML: {$e->getMessage()}", 0, $e);
        }

        // Validate structure
        $containerNode = $this->resolveContainerNode($xml);
        if ($containerNode === null) {
            throw new CurrencyRateProviderInvalidRateDataException('Invalid XML structure: missing ValCurs node');
        }

        $rateDate = $this->parseRateDate($containerNode);
        if ($rateDate === null) {
            throw new CurrencyRateProviderInvalidRateDataException('Invalid XML: missing or invalid date attribute');
        }

        if (!DateCompare::eq($rateDate, $date)) {
            throw new CurrencyRateProviderInvalidRateDataException(
                "Rates date is not current: {$rateDate->format('Y-m-d')}"
            );
        }

        // Process and yield rates
        $processedCount = 0;
        foreach ($containerNode->Valute as $currencyNode) {
            $currencyCode = (string) ($currencyNode->CharCode ?? '');

            if (empty($currencyCode)) {
                throw new CurrencyRateProviderInvalidRateDataException('Invalid currency: missing CharCode');
            }

            if (!in_array($currencyCode, $monitoredCurrencies, true)) {
                continue;
            }

            // todo: rate precision should be handled by provider config, not hardcoded
            $rateValue = $this->processRate($currencyCode, $currencyNode, $ratePrecision);

            yield new RateContainer(
                new Currency($baseCurrencyCode),
                new Currency($currencyCode),
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
     * @throws CurrencyRateProviderInvalidRateDataException
     */
    private function processRate(string $currencyCode, SimpleXMLElement $currencyNode): string
    {
        if (!$currencyNode->VunitRate) {
            throw new CurrencyRateProviderInvalidRateDataException(
                "Invalid rate data for {$currencyCode}: missing VunitRate"
            );
        }

        return NumberHelper::normalize((string) $currencyNode->VunitRate);
    }
}
