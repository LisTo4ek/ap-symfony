<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use Money\Currency;

class RateHistoryPaginatorService
{
    /**
     * @param PaginationServiceInterface<RateHistory> $paginator
     */
    public function __construct(
        private readonly PaginationServiceInterface $paginator,
        private readonly RateHistoryStorageInterface $storage,
    ) {
    }

    /**
     * @return PaginationResultInterface<RateHistory>|null
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
