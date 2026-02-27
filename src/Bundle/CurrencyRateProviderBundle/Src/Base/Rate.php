<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use DateTimeInterface;

class Rate
{
    public function __construct(
        public CurrencyContract $baseCurrency,
        public CurrencyContract $targetCurrency,
        public string $rate,
        public DateTimeInterface $date
    ) {
    }
}
