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

class CurrentRatesGetterService
{
    /**
     * @param PaginationServiceInterface<CurrentRate> $paginator
     */
    public function __construct(
        private readonly PaginationServiceInterface $paginator,
        private readonly CurrentRateStorageInterface $currentRateStorage,
        private readonly CurrencyRateHistoryCbrProcessorService $currencyRateHistoryProcessorService,
    ) {
    }

    /**
     * @return array{0: DateTimeInterface|null, 1: PaginationResultInterface<CurrentRate>|null}
     */
    public function get(
        PaginationConfigInterface $paginatorConfig,
        CurrentRateContainer $dto,
    ): array {
        $today = new DateTimeImmutable('today');
        $latestDate = $this->currentRateStorage->getLatestDate();

        if (!$latestDate || !DateCompare::eq($today, $latestDate)) {
            try {
                $this->currencyRateHistoryProcessorService->process($today);
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
