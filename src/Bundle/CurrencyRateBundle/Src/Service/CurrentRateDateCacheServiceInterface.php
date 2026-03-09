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
    public function get(bool $warmUp = false): ?DateTimeInterface;
    public function set(?DateTimeInterface $date, ?int $ttl = null): void;
    public function warmUp(): ?DateTimeInterface;
}
