<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\InvalidRateDataException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use DateTimeImmutable;
use Generator;

interface RateProcessorContract
{
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
    ): Generator;
}
