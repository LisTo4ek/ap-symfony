<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

#[AsAlias(CurrentRateDateCacheServiceInterface::class)]
class CurrentRateDateCacheService implements CurrentRateDateCacheServiceInterface
{
    private const string CACHE_KEY = 'current_rate_date';
    private const int DEFAULT_TTL = 86400 * 30; // 30 days
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        #[Target('currency_rates_cache')]
        private readonly CacheInterface $cache,
        private readonly CurrentRateStorageInterface $storage,
    ) {
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
}
