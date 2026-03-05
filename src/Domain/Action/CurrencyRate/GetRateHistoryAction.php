<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRate;

use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorConfigContract;
use App\Domain\Contracts\Pagination\PaginatorContract;
use App\Domain\Dto\CurrencyRate\RateHistoryDto;
use Money\Currency;

class GetRateHistoryAction
{
    public function __construct(
        private readonly PaginatorContract $paginator,
        private readonly RateHistoryStorageContract $storage,
    ) {
    }

    public function __invoke(
        PaginatorConfigContract $paginatorConfig,
        RateHistoryDto $dto,
    ): ?PaginationResultContract {
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
