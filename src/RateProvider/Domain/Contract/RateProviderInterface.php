<?php

namespace App\RateProvider\Domain\Contract;

use App\RateProvider\Domain\Entity\Rate;

/**
 * Interface for currency rate providers
 * Allows different implementations (CBR, ECB, etc.)
 */
interface RateProviderInterface
{
    /**
     * Get currency rates for a specific date
     *
     * @param \DateTimeInterface $date The date to fetch rates for
     * @return Rate[] Array of Rate domain entities
     * @throws \RuntimeException when service is unavailable
     */
    public function getRates(\DateTimeInterface $date): array;
}

