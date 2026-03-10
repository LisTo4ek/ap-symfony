<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\InvalidRateDataException;
use DateTimeImmutable;
use Generator;

/**
 * Contract for parsing raw currency rate content into RateContainer objects.
 *
 * Implementations handle format-specific parsing (e.g. XML from CBR)
 * and yield domain objects for each monitored currency found.
 */
interface CurrencyRateParserServiceInterface
{
    /**
     * Parses raw content and yields RateContainer objects for monitored currencies.
     *
     * @param string              $content              Raw response content to parse
     * @param string              $baseCurrencyCode     ISO 4217 base currency code
     * @param array<string>       $monitoredCurrencies  List of target ISO 4217 currency codes to extract
     * @param DateTimeImmutable   $date                 Expected date of the rates
     *
     * @return Generator<int, RateContainer> Yields a RateContainer for each matched currency
     *
     * @throws InvalidRateDataException When parsing or validation fails
     */
    public function parse(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        DateTimeImmutable $date,
    ): Generator;
}
