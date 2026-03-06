<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use DateTimeInterface;
use Money\Currency;

class RateContainer
{
    public function __construct(
        public Currency $baseCurrency,
        public Currency $targetCurrency,
        public string $rate,
        public DateTimeInterface $date
    ) {
    }
}
