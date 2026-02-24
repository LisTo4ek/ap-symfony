<?php

namespace App\RateProvider\Infrastructure\Providers\CbrRateProvider;

use App\RateProvider\Domain\Entity\Rate;
use App\RateProvider\Domain\ValueObject\Currency;

class CbrXmlRateParser
{
    /**
     * @param array<Currency> $monitoredCurrencies
     * @return \Generator<int, Rate>
     */
    public function parse(
        string $xmlContent,
        Currency $baseCurrency,
        array $monitoredCurrencies,
        string $encoding = 'UTF-8'
    ): \Generator {
        $normalizedXml = $this->normalizeEncoding($xmlContent, $encoding);
        $xml = new \SimpleXMLElement($normalizedXml);

        $valCurs = $this->resolveValCursNode($xml);
        if ($valCurs === null) {
            return;
        }

        $rateDate = $this->parseRateDate($valCurs);

        foreach ($valCurs->Valute as $val) {
            $targetCurrency = Currency::tryFrom($val->CharCode);

            if (!in_array($targetCurrency, $monitoredCurrencies, true)) {
                continue;
            }

            yield new Rate(
                $baseCurrency,
                $targetCurrency,
                (float) str_replace(',', '.', (string) $val->VunitRate),
                $rateDate
            );
        }
    }

    private function parseRateDate(\SimpleXMLElement $valCurs): \DateTimeImmutable
    {
        $dateValue = (string) ($valCurs['Date'] ?? '');
        if ($dateValue !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('d.m.Y', $dateValue);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed;
            }
        }

        return new \DateTimeImmutable();
    }

    private function resolveValCursNode(\SimpleXMLElement $xml): ?\SimpleXMLElement
    {
        if ($xml->getName() === 'ValCurs') {
            return $xml;
        }

        if (isset($xml->ValCurs)) {
            return $xml->ValCurs;
        }

        return null;
    }

    private function normalizeEncoding(string $xmlContent, string $encoding): string
    {
        if ($encoding === '' || strtoupper($encoding) === 'UTF-8') {
            return $xmlContent;
        }

        $converted = @iconv($encoding, 'UTF-8//IGNORE', $xmlContent);
        return $converted !== false ? $converted : $xmlContent;
    }
}
