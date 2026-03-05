<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base;

use DateTimeInterface;
use Money\Currency;

class Rate
{
    public function __construct(
        public Currency $baseCurrency,
        public Currency $targetCurrency,
        public string $rate,
        public DateTimeInterface $date
    ) {
    }
}
