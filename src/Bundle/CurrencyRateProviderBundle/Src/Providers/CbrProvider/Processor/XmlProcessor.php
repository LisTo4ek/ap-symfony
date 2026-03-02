<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\InvalidRateDataException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLoggerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Domain\Helper\NumberHelper;
use DateTimeImmutable;
use Generator;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

#[AsAlias(RateProcessorContract::class)]
class XmlProcessor implements RateProcessorContract
{
    public function __construct(
        private CurrencyManagerContract $currencyManager,
        private CurrencyRateProviderLoggerContract $logger,
    ) {
    }

    /**
     * @param array<string> $monitoredCurrencies
     * @return Generator<int, Rate>
     * @throws InvalidRateDataException when XML processing fails
     */
    public function process(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        int $ratePrecision,
        DateTimeImmutable $date,
    ): Generator {
        $this->logger->debug('Processing XML response', [
            'content_size' => strlen($content),
            'date' => $date->format('Y-m-d'),
        ]);

        try {
            $xml = new SimpleXMLElement($content);
        } catch (Throwable $e) {
            throw new InvalidRateDataException(
                "Invalid XML: {$e->getMessage()}",
                0,
                $e
            );
        }

        // Validate structure
        $containerNode = $this->resolveContainerNode($xml);
        if ($containerNode === null) {
            throw new InvalidRateDataException('Invalid XML structure: missing ValCurs node');
        }

        $rateDate = $this->parseRateDate($containerNode);
        if ($rateDate === null) {
            throw new InvalidRateDataException('Invalid XML: missing or invalid date attribute');
        }

        if ($rateDate->diff($date)->days !== 0) {
            throw new InvalidRateDataException(
                "Rates date is not current: {$rateDate->format('Y-m-d')}"
            );
        }

        // Process and yield rates
        $processedCount = 0;
        foreach ($containerNode->Valute as $currencyNode) {
            $currencyCode = (string) ($currencyNode->CharCode ?? '');

            if (empty($currencyCode)) {
                throw new InvalidRateDataException('Invalid currency: missing CharCode');
            }

            if (!in_array($currencyCode, $monitoredCurrencies, true)) {
                continue;
            }

            $targetCurrency = $this->currencyManager::create($currencyCode);
            $rateValue = $this->processRate($currencyCode, $currencyNode, $ratePrecision);

            yield new Rate(
                $this->currencyManager::create($baseCurrencyCode),
                $targetCurrency,
                $rateValue,
                $rateDate,
            );

            $processedCount++;
        }

        // Log success
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
     * @throws InvalidRateDataException
     */
    private function processRate(string $currencyCode, SimpleXMLElement $currencyNode, int $ratePrecision): string
    {
        foreach (['VunitRate', 'Nominal'] as $nodeName) {
            if (!isset($currencyNode->{$nodeName})) {
                throw new InvalidRateDataException(
                    "Invalid rate data for {$currencyCode}: missing {$nodeName}"
                );
            }
        }

        $rate = NumberHelper::normalize((string) $currencyNode->VunitRate);
        $base = NumberHelper::normalize((string) $currencyNode->Nominal);

        return bcdiv($rate, $base, $ratePrecision);
    }
}
