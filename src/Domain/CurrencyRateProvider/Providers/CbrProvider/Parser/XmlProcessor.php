<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Providers\CbrProvider\Parser;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Base\Entity\Rate;
use DateTimeImmutable;
use Exception;
use Generator;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(RateProcessorInterface::class)]
class XmlProcessor implements RateProcessorInterface
{
    /**
     * @param array<CurrencyEnum> $monitoredCurrencies
     * @return Generator<int, Rate>
     * @throws Exception
     */
    public function process(
        string $content,
        CurrencyEnum $baseCurrency,
        array $monitoredCurrencies,
        string $encoding = 'UTF-8'
    ): Generator {
        $xml = new SimpleXMLElement($content);

        $containerNode = $this->resolveContainerNode($xml);
        if ($containerNode === null) {
            return;
        }

        $rateDate = $this->parseRateDate($containerNode);

        foreach ($containerNode->Valute as $currencyNode) {
            $targetCurrency = CurrencyEnum::tryFrom((string) $currencyNode->CharCode);

            if (!in_array($targetCurrency, $monitoredCurrencies, true)) {
                continue;
            }

            yield new Rate(
                $baseCurrency,
                $targetCurrency,
                (float) \str_replace(',', '.', (string) $currencyNode->VunitRate),
                $rateDate,
            );
        }
    }

    private function parseRateDate(SimpleXMLElement $valCurs): DateTimeImmutable
    {
        $dateValue = (string) ($valCurs['Date'] ?? '');
        if ($dateValue !== '') {
            $parsed = DateTimeImmutable::createFromFormat('d.m.Y', $dateValue);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        return new DateTimeImmutable();
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
}
