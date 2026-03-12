<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderException;
use DateTimeImmutable;
use Generator;
use RuntimeException;

/**
 * Interface for currency rate providers
 * Allows different implementations (CBR, ECB, etc.)
 */
interface CurrencyRateProviderServiceInterface
{
    /**
     * Get currency rates for a specific date as a generator, chunked
     *
     * @param DateTimeImmutable $date The date to fetch rates for
     * @param int $chunkSize Number of items per chunk (default: 100)
     *
     * @return Generator<int, array<RateContainer>> Generator yielding chunks of Rate domain entities
     *
     * @throws ProviderException When there is an error fetching or processing rates
     */
    public function getRates(DateTimeImmutable $date, int $chunkSize = 1000): Generator;
}
