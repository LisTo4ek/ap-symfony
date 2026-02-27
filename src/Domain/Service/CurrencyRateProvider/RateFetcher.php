<?php

declare(strict_types=1);

namespace App\Domain\Service\CurrencyRateProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use DateTimeInterface;
use Generator;

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
     * @return Generator<int, array<Rate>>
     * @throws \RuntimeException when service is unavailable
     */
    public function fetchRates(DateTimeInterface $date, int $chunkSize = 1000): Generator
    {
        return $this->rateProvider->getRates($date, $chunkSize);
    }

    /**
     * Try to fetch rates, return null on error
     * @return Generator<int, array<Rate>>|null
     */
    public function tryFetchRates(DateTimeInterface $date): ?Generator
    {
        try {
            return $this->fetchRates($date);
        } catch (\Exception $e) {
            // TODO: Log the exception using a logger service
            return null;
        }
    }
}

