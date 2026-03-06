<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use DateTimeInterface;
use Money\Currency;

class RateContainer
{
    public function __construct(
        public readonly Currency $baseCurrency,
        public readonly Currency $targetCurrency,
        public readonly string $rate,
        public readonly DateTimeInterface $date
    ) {
    }
}
