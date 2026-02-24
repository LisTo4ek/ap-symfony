<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Service;

use App\Domain\CurrencyRateProvider\Base\Entity\Rate;
use App\Domain\CurrencyRateProvider\Providers\CurrencyRateProviderInterface;
use DateTimeInterface;

/**
 * Service for fetching currency rates from external sources
 */
class RateFetcher
{
    public function __construct(
        private readonly CurrencyRateProviderInterface $rateProvider
    ) {
    }

    /**
     * Fetch rates for a specific date
     * @return Rate[]
     * @throws \RuntimeException when service is unavailable
     */
    public function fetchRates(DateTimeInterface $date): array
    {
        return $this->rateProvider->getRates($date);
    }

    /**
     * Try to fetch rates, return null on error
     * @return Rate[]|null
     */
    public function tryFetchRates(DateTimeInterface $date): ?array
    {
        try {
            return $this->fetchRates($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}

