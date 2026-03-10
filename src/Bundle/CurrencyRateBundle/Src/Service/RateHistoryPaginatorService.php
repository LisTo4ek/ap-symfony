<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use Money\Currency;

/**
 * Service that retrieves paginated rate history for a specific currency pair.
 *
 * Delegates to the storage layer for querying and the pagination service for slicing results.
 */
class RateHistoryPaginatorService
{
    /**
     * @param PaginationServiceInterface<RateHistory> $paginator Pagination service for slicing query results
     * @param RateHistoryStorageInterface             $storage   Storage for querying rate history records
     */
    public function __construct(
        private readonly PaginationServiceInterface $paginator,
        private readonly RateHistoryStorageInterface $storage,
    ) {
    }

    /**
     * Retrieves a paginated list of rate history records for the currency pair specified in the DTO.
     *
     * Returns null if either the base or target currency code is empty.
     *
     * @param PaginationConfigInterface $paginatorConfig Pagination configuration (allowed per-page options, etc.)
     * @param RateHistoryContainer      $dto             Validated request DTO with pagination and currency pair codes
     *
     * @return PaginationResultInterface<RateHistory>|null Paginated results, or null if currency codes are missing
     */
    public function get(
        PaginationConfigInterface $paginatorConfig,
        RateHistoryContainer $dto,
    ): ?PaginationResultInterface {
        if (empty($dto->baseCurrencyCode) || empty($dto->targetCurrencyCode)) {
            return null;
        }

        return $this->paginator->paginate(
            $this->storage->findByCurrencyPair(
                new Currency($dto->baseCurrencyCode),
                new Currency($dto->targetCurrencyCode),
            ),
            $paginatorConfig,
            $dto->pagination->perPage,
            $dto->pagination->page,
        );
    }
}
