<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception\FailedToGetRatesException;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use DateTimeImmutable;
use Generator;

interface RateProcessorInterface
{
    /**
     * @param array<string> $monitoredCurrencies
     * @return Generator<int, Rate>
     * @throws FailedToGetRatesException when processing fails
     */
    public function process(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        int $ratePrecision,
        DateTimeImmutable $date,
    ): Generator;
}
