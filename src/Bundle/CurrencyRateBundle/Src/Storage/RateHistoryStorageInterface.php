<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use Money\Currency;

/**
 * Contract for persisting and querying historical exchange rate records.
 *
 * Abstracts the storage layer so that the service layer is decoupled
 * from the concrete Doctrine repository implementation.
 */
interface RateHistoryStorageInterface
{
    /**
     * Persists a batch of RateHistory entities in a single transaction.
     *
     * @param array<RateHistory> $entities The rate history entities to save
     */
    public function saveBatch(array $entities): void;

    /**
     * Returns a pageable query for rate history records filtered by a currency pair.
     *
     * @param Currency $baseCurrency The base currency to filter by
     * @param Currency $targetCurrency The target currency to filter by
     *
     * @return PaginationPageableServiceInterface<RateHistory> Pageable query adapter
     */
    public function findByCurrencyPairGroupedByDate(
        Currency $baseCurrency,
        Currency $targetCurrency,
    ): PaginationPageableServiceInterface;
}
