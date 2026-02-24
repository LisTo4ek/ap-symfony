<?php

namespace App\RateProvider\Domain\Entity;

use App\RateProvider\Domain\ValueObject\Currency;

class Rate
{
    public function __construct(
        public readonly Currency $baseCurrency,
        public readonly Currency $targetCurrency,
        public readonly float $value,
        public readonly \DateTimeImmutable $date
    ) {
    }
}
