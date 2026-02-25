<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Base\Entity;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use DateTimeInterface;

class Rate
{
    public function __construct(
        public readonly CurrencyEnum $baseCurrency,
        public readonly CurrencyEnum $targetCurrency,
        public readonly string $rate,
        public readonly DateTimeInterface $date
    ) {
    }
}
