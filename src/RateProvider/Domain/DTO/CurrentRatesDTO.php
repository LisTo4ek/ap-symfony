<?php

namespace App\RateProvider\Domain\DTO;

use App\Entity\CurrentRate;

/**
 * DTO for current rates response
 *
 * @property CurrentRate[] $rates - Array of current exchange rates
 * @property \DateTimeInterface $date - Date of the rates
 * @property bool $isActual - Whether rates are current (today's rates)
 * @property string|null $message - Error or warning message if any
 */
class CurrentRatesDTO
{
    /**
     * @param CurrentRate[] $rates
     */
    public function __construct(
        public readonly array $rates,
        public readonly \DateTimeInterface $date,
        public readonly bool $isActual,
        public readonly ?string $message = null
    ) {
    }

    public function hasRates(): bool
    {
        return !empty($this->rates);
    }

    public function hasWarning(): bool
    {
        return $this->message !== null;
    }
}

