<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\Service;

use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\Contracts\CurrencyRate\CurrentRateDateCacheContract;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Manages caching of the latest saved rate date
 *
 * Responsible for:
 * - Checking if cache should be updated
 * - Updating cache with new date
 * - Comparing dates for update logic
 */
#[AsAlias(CurrentRateDateCacheContract::class)]
class CurrentRateDateCache implements CurrentRateDateCacheContract
{
    private const string CACHE_KEY = 'current_rate_date';
    private const int DEFAULT_TTL = 86400 * 30; // 30 days
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        #[Target('currency_rates_cache')]
        private readonly CacheInterface $cache,
        private readonly CurrentRateStorageContract $storage,
    ) {
    }

    /**
     * Update the cache with the given date
     *
     * Deletes old value and caches new value for 30 days
     *
     * @param DateTimeImmutable $date The date to cache
     * @param int|null $ttl Optional TTL in seconds (default: 30 days)
     * @return void
     */
    public function update(DateTimeInterface $date, ?int $ttl = null): void
    {
        try {
            $ttl ??= self::DEFAULT_TTL;

            // Delete old value to force cache miss on next read
            $this->cache->delete(self::CACHE_KEY);

            // Now get() will execute the callable and cache the new value
            $this->cache->get(self::CACHE_KEY, function () use ($date) {
                return $date->format(self::DATE_FORMAT);
            }, $ttl);
        } catch (\Exception) {
            // Silently fail - logging is handled elsewhere
        }
    }

    public function get(bool $warmUp = false): ?DateTimeInterface
    {
        $value = $this->cache->get(self::CACHE_KEY, function () {
            return null;
        });

        if ($value === null && $warmUp) {
            return $this->warmUp();
        }

        return $value ? new DateTimeImmutable($value) : null;
    }

    public function set(?DateTimeInterface $date = null, ?int $ttl = null): void
    {
        $ttl ??= self::DEFAULT_TTL;

        $this->cache->delete(self::CACHE_KEY);
        $this->cache->get(self::CACHE_KEY, function () use ($date) {
            return $date->format(self::DATE_FORMAT);
        }, $ttl);
    }

    public function warmUp(): ?DateTimeInterface
    {
        $value = $this->storage->getLatestDate();

        if ($value === null) {
            return null;
        }

        $this->set($value);

        return $value;
    }

    /**
     * Get the cached date if it exists
     *
     * @return DateTimeImmutable|null The cached date or null if not set
     */
    public function getCachedDate(): ?DateTimeImmutable
    {
        try {
            $cachedDate = $this->cache->get(self::CACHE_KEY, function () {
                return null;
            });

            if ($cachedDate === null) {
                return null;
            }

            return new DateTimeImmutable($cachedDate);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get the cached date as string
     *
     * @return string|null The cached date as string (Y-m-d) or null if not set
     */
    public function getCachedDateAsString(): ?string
    {
        try {
            return $this->cache->get(self::CACHE_KEY, function () {
                return null;
            });
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Clear the cache
     *
     * @return void
     */
    public function clear(): void
    {
        try {
            $this->cache->delete(self::CACHE_KEY);
        } catch (\Exception) {
            // Silently fail
        }
    }

    /**
     * Warm up the cache with current date if it's empty
     *
     * Sets cache to today's date only if cache is not already set
     *
     * @return bool True if cache was warmed up, false if it already had a value
     */
    public function warmupIfEmpty(): bool
    {
        try {
            $cachedDate = $this->getCachedDate();

            // If cache is not empty, don't warm up
            if ($cachedDate !== null) {
                return false;
            }

            // Cache is empty, warm it up with today's date
            $today = new DateTimeImmutable('today');
            $this->update($today);

            return true;
        } catch (\Exception) {
            // If any error occurs, silently fail
            return false;
        }
    }
}


