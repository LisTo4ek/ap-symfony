<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use Brick\Math\BigDecimal;
use DateTimeInterface;
use Money\Currency;

/**
 * Immutable value object representing a single currency exchange rate.
 *
 * Carries the base currency, target currency, the rate value, and the date
 * the rate applies to. Used throughout the bundle for passing rate data
 * between services, events, and storage layers.
 */
class RateContainer
{
    /**
     * @param Currency          $baseCurrency   The base (source) currency of the rate
     * @param Currency          $targetCurrency The target (destination) currency of the rate
     * @param BigDecimal        $rate           The exchange rate value (base → target)
     * @param DateTimeInterface $date           The date the rate is effective for
     */
    public function __construct(
        public readonly Currency $baseCurrency,
        public readonly Currency $targetCurrency,
        public readonly BigDecimal $rate,
        public readonly DateTimeInterface $date
    ) {
    }
}
