<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Providers;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use DateTimeImmutable;
use Generator;
use RuntimeException;

/**
 * Interface for currency rate providers
 * Allows different implementations (CBR, ECB, etc.)
 */
interface CurrencyRateProviderContract
{
    /**
     * Get currency rates for a specific date as a generator, chunked
     *
     * @param DateTimeImmutable $date The date to fetch rates for
     * @param int $chunkSize Number of items per chunk (default: 100)
     * @return Generator<int, array<Rate>> Generator yielding chunks of Rate domain entities
     * @throws RuntimeException when service is unavailable
     */
    public function getRates(DateTimeImmutable $date, int $chunkSize = 1000): Generator;
}
