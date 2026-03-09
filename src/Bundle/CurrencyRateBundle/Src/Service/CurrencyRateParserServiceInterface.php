<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderInvalidRateDataException;
use DateTimeImmutable;
use Generator;

interface CurrencyRateParserServiceInterface
{
    /**
     * @param array<string> $monitoredCurrencies
     * @return Generator<int, RateContainer>
     * @throws CurrencyRateProviderInvalidRateDataException when XML processing fails
     */
    public function parse(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        DateTimeImmutable $date,
    ): Generator;
}
