<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Entity\Rate;

interface RateProcessorInterface
{
    /**
     * @param array<string> $monitoredCurrencies
     * @return \Generator<int, Rate>
     */
    public function process(
        string $content,
        string $baseCurrencyCode,
        array $monitoredCurrencies,
        int $ratePrecision,
    ): \Generator;
}
