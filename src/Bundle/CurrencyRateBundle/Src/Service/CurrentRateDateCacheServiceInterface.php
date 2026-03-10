<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTimeInterface;

/**
 * Manages caching of the latest saved rate date
 *
 * Responsible for:
 * - Checking if cache should be updated
 * - Updating cache with new date
 * - Comparing dates for update logic
 */
interface CurrentRateDateCacheServiceInterface
{
    /**
     * Retrieves the cached latest rate date, optionally warming up the cache if empty.
     *
     * @param bool $warmUp Whether to query the database and populate the cache when no value exists
     *
     * @return DateTimeInterface|null The latest rate date, or null if no rates exist
     */
    public function get(bool $warmUp = false): ?DateTimeInterface;

    /**
     * Replaces or clears the cached latest rate date.
     *
     * @param DateTimeInterface|null $date The date to cache, or null to clear
     * @param int|null               $ttl  Cache TTL in seconds
     */
    public function set(?DateTimeInterface $date, ?int $ttl = null): void;

    /**
     * Queries the database for the latest rate date and populates the cache.
     *
     * @return DateTimeInterface|null The latest rate date, or null if no rates exist
     */
    public function warmUp(): ?DateTimeInterface;
}
