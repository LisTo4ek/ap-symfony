<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Providers;

use App\Domain\CurrencyRateProvider\Base\Entity\Rate;
use DateTimeInterface;
use Generator;
use RuntimeException;

/**
 * Interface for currency rate providers
 * Allows different implementations (CBR, ECB, etc.)
 */
interface CurrencyRateProviderInterface
{
    /**
     * Get currency rates for a specific date as a generator, chunked
     *
     * @param DateTimeInterface $date The date to fetch rates for
     * @param int $chunkSize Number of items per chunk (default: 100)
     * @return Generator<int, array<Rate>> Generator yielding chunks of Rate domain entities
     * @throws RuntimeException when service is unavailable
     */
    public function getRates(DateTimeInterface $date, int $chunkSize = 100): Generator;
}

