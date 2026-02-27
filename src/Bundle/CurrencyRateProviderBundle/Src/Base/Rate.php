<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use DateTimeInterface;

class Rate
{
    public function __construct(
        public readonly CurrencyContract $baseCurrency,
        public readonly CurrencyContract $targetCurrency,
        public readonly string $rate,
        public readonly DateTimeInterface $date
    ) {
    }
}
