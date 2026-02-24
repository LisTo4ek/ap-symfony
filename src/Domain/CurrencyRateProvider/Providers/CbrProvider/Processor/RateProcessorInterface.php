<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Providers\CbrProvider\Processor;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Base\Entity\Rate;

interface RateProcessorInterface
{
    /**
     * @param array<CurrencyEnum> $monitoredCurrencies
     * @return \Generator<int, Rate>
     */
    public function process(
        string       $content,
        CurrencyEnum $baseCurrency,
        array        $monitoredCurrencies,
        string       $encoding = 'UTF-8'
    ): \Generator;
}
