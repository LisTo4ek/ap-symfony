<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRate;

use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorConfigContract;
use App\Domain\Contracts\Pagination\PaginatorContract;
use App\Domain\Dto\CurrencyRate\CurrentRateDto;
use DateTimeInterface;
use Money\Currency;

class GetCurrentRatesAction
{
    public function __construct(
        private readonly PaginatorContract $paginator,
        private readonly CurrentRateStorageContract $currentRateStorage,
    ) {
    }

    /**
     * @return array{0: DateTimeInterface|null, 1: PaginationResultContract|null}
     */
    public function __invoke(
        PaginatorConfigContract $paginatorConfig,
        CurrentRateDto $dto,
    ): array {
        $latestDate = $this->currentRateStorage->getLatestDate();

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
