<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Domain\Helper\NumberHelper;
use DateTimeImmutable;
use Exception;
use Generator;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(RateProcessorInterface::class)]
class XmlProcessor implements RateProcessorInterface
{
    public function __construct(
        private CurrencyManagerContract $currencyManager,
    ) {
    }

    /**
     * @param array<string> $monitoredCurrencies
     * @return Generator<int, Rate>
     * @throws Exception
     */
    public function process(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        int $ratePrecision,
    ): Generator {
        $xml = new SimpleXMLElement($content);

        $containerNode = $this->resolveContainerNode($xml);
        if ($containerNode === null) {
            return;
        }

        $rateDate = $this->parseRateDate($containerNode);

        foreach ($containerNode->Valute as $currencyNode) {
            $targetCurrency = $this->currencyManager::create((string) $currencyNode->CharCode);

            if (!in_array($targetCurrency->getCode(), $monitoredCurrencies, true)) {
                continue;
            }

            yield new Rate(
                $this->currencyManager::create($baseCurrencyCode),
                $targetCurrency,
                $this->processRate($currencyNode, $ratePrecision),
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

    private function processRate(SimpleXMLElement $currencyNode, int $ratePrecision): string
    {
        $rate = NumberHelper::normalize((string) $currencyNode->VunitRate);
        $base = NumberHelper::normalize((string) $currencyNode->Nominal);

        return bcdiv($rate, $base, $ratePrecision);
    }
}
