<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;

/**
 * Contract for persisting and querying current (latest) exchange rates.
 *
 * Abstracts the storage layer so that the service layer is decoupled
 * from the concrete Doctrine repository implementation.
 */
interface CurrentRateStorageInterface
{
    /**
     * Inserts or updates the current rate for a specific currency pair.
     *
     * @param Currency          $baseCurrency   The base (source) currency
     * @param Currency          $targetCurrency The target (destination) currency
     * @param BigDecimal        $value          The exchange rate value
     * @param DateTimeInterface $date           The date the rate applies to
     *
     * @return CurrentRate The persisted entity
     */
    public function upsertForCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
        BigDecimal $value,
        DateTimeInterface $date,
    ): CurrentRate;

    /**
     * Checks whether any current rate records exist for the given date.
     *
     * @param DateTimeImmutable $date The date to check
     *
     * @return bool True if at least one record exists
     */
    public function hasRecordsByDay(DateTimeImmutable $date): bool;

    /**
     * Returns the most recent date for which current rate records exist.
     *
     * @return DateTimeImmutable|null The latest date, or null if no records exist
     */
    public function getLatestDate(): ?DateTimeImmutable;

    /**
     * Returns a pageable query for current rates filtered by date and base currency.
     *
     * @param DateTimeImmutable $date         The date to filter by
     * @param Currency          $baseCurrency The base currency to filter by
     *
     * @return PaginationPageableServiceInterface<CurrentRate> Pageable query adapter
     */
    public function findByDateAndBaseCurrency(
        DateTimeImmutable $date,
        Currency $baseCurrency,
    ): PaginationPageableServiceInterface;
}
