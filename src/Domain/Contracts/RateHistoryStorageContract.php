<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Entity\RateHistory;
use DateTimeInterface;

/**
 * Port interface for rate history persistence
 *
 * This interface defines the contract for storing and retrieving rate history.
 * Implementation details (Doctrine, Redis, API, etc.) are hidden from domain layer.
 */
interface RateHistoryStorageContract
{
    /**
     * Save a single rate history record
     */
    public function save(RateHistory $entity, bool $flush = false): void;

    /**
     * Save multiple rate history records in batch
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;

    /**
     * Find rate history by currency pair and date
     */
    public function findByCurrencyPairAndDate(
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
        DateTimeInterface $date
    ): ?RateHistory;

    /**
     * Find rate history by currency pair and date range
     * @return array<RateHistory>
     */
    public function findByCurrencyPairAndDateRange(
        CurrencyContract $baseCurrency,
        ?CurrencyContract $targetCurrency,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array;

    /**
     * Update or create rate history entry
     */
    public function updateOrCreate(
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
        string $value,
        DateTimeInterface $date
    ): RateHistory;
}

