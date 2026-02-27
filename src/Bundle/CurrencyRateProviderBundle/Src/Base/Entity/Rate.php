<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Entity;

use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;
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
