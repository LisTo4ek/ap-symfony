<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Caching service for the latest saved current-rate date.
 *
 * Uses Symfony's CacheInterface (tagged 'currency_rates_cache') to store
 * the most recent date for which current rates exist, avoiding repeated
 * database queries. Supports warm-up and manual cache invalidation.
 */
#[AsAlias(CurrentRateDateCacheServiceInterface::class)]
class CurrentRateDateCacheService implements CurrentRateDateCacheServiceInterface
{
    /** @var string Cache key for storing the latest rate date */
    private const string CACHE_KEY = 'current_rate_date';

    /** @var int Default cache TTL in seconds (30 days) */
    private const int DEFAULT_TTL = 86400 * 30; // 30 days

    /** @var string Date format used for cache serialization */
    private const string DATE_FORMAT = 'Y-m-d';

    /**
     * @param CacheInterface $cache Symfony cache pool for currency rate data
     * @param CurrentRateStorageInterface $storage Storage for querying the latest date from the database
     */
    public function __construct(
        #[Target('currency_rates_cache')]
        private readonly CacheInterface $cache,
        private readonly CurrentRateStorageInterface $storage,
    ) {
    }

    /**
     * Retrieves the cached latest rate date, optionally warming up the cache if empty.
     *
     * On cache miss the value is fetched from the database and cached.
     * If the result is still null and $warmUp is true, triggers a full warm-up.
     *
     * @param bool $warmUp Whether to query the database and populate the cache when no value exists
     *
     * @return DateTimeInterface|null The latest rate date, or null if no rates exist
     * @throws DateMalformedStringException
     * @throws InvalidArgumentException
     */
    public function get(bool $warmUp = false): ?DateTimeInterface
    {
        $cached = $this->cache->get(self::CACHE_KEY, function () {
            $latestDate = $this->storage->getLatestDate();

            return $latestDate?->format(self::DATE_FORMAT);
        });

        if ($cached === null && $warmUp) {
            return $this->warmUp();
        }

        return $cached !== null ? new DateTimeImmutable($cached) : null;
    }

    /**
     * Replaces or clears the cached latest rate date.
     *
     * Deletes the existing cache entry and, if a date is provided, stores it with the given TTL.
     *
     * @param DateTimeInterface|null $date The date to cache, or null to clear the cache
     * @param int|null $ttl Cache TTL in seconds (defaults to 30 days)
     * @throws InvalidArgumentException
     */
    public function set(?DateTimeInterface $date = null, ?int $ttl = null): void
    {
        $ttl ??= self::DEFAULT_TTL;

        $this->cache->delete(self::CACHE_KEY);

        if ($date !== null) {
            $this->cache->get(self::CACHE_KEY, function () use ($date) {
                return $date->format(self::DATE_FORMAT);
            }, $ttl);
        }
    }

    /**
     * Queries the database for the latest rate date and populates the cache.
     *
     * @return DateTimeInterface|null The latest rate date from the database, or null if no rates exist

     * @throws InvalidArgumentException
     */
    public function warmUp(): ?DateTimeInterface
    {
        $value = $this->storage->getLatestDate();

        if ($value === null) {
            return null;
        }

        $this->set($value);

        return $value;
    }
}
