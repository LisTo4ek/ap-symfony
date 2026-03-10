<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Helper\DateCompare;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;
use Throwable;

/**
 * Service that retrieves current exchange rates for display.
 *
 * Checks whether today's rates are already stored; if not, triggers an import
 * from the CBR provider. Returns the latest available date and a paginated
 * list of CurrentRate entities filtered by base currency.
 */
class CurrentRatesGetterService
{
    /**
     * @param PaginationServiceInterface<CurrentRate> $paginator          Pagination service for slicing query results
     * @param CurrentRateStorageInterface             $currentRateStorage Storage for querying current rate records
     * @param CurrencyRateHistoryCbrProcessorService  $processor          Processor for importing rates from CBR
     */
    public function __construct(
        private readonly PaginationServiceInterface $paginator,
        private readonly CurrentRateStorageInterface $currentRateStorage,
        private readonly CurrencyRateHistoryCbrProcessorService $processor,
    ) {
    }

    /**
     * Retrieves the latest rate date and a paginated list of current rates for a base currency.
     *
     * If today's rates are not yet stored, attempts to import them from CBR first.
     * Returns null pagination if the base currency code is empty or no rates exist.
     *
     * @param PaginationConfigInterface $paginatorConfig Pagination configuration (per-page options, etc.)
     * @param CurrentRateContainer      $dto             Validated request DTO with pagination and base currency
     *
     * @return array{0: DateTimeInterface|null, 1: PaginationResultInterface<CurrentRate>|null}
     *         Tuple of [latest rate date, paginated current rates or null]
     */
    public function get(
        PaginationConfigInterface $paginatorConfig,
        CurrentRateContainer $dto,
    ): array {
        $today = new DateTimeImmutable('today');
        $latestDate = $this->currentRateStorage->getLatestDate();

        if (!$latestDate || !DateCompare::eq($today, $latestDate)) {
            try {
                $this->processor->process($today);
                $latestDate = $this->currentRateStorage->getLatestDate();
            } catch (Throwable) {
                // do nothing
            }
        }

        if (empty($dto->baseCurrencyCode)) {
            return [$latestDate, null];
        }

        return [
            $latestDate,
            $latestDate
                ? $this->paginator->paginate(
                    $this->currentRateStorage->findByDateAndBaseCurrency(
                        $latestDate,
                        new Currency($dto->baseCurrencyCode)
                    ),
                    $paginatorConfig,
                    $dto->pagination->perPage,
                    $dto->pagination->page,
                )
                : null
        ];
    }
}
