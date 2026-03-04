<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use App\Domain\Contracts\Pagination\ItemsPerPageContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorContract;
use App\Domain\Pagination\ItemsPerPageDefault;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GetRateHistoryAction
{
    public function __construct(
        private readonly PaginatorContract $paginator,
        private readonly RateHistoryStorageContract $rateHistoryStorage,
        private readonly CurrencyManagerContract $currencyManager,
        #[Autowire(service: ItemsPerPageDefault::class)]
        private readonly ItemsPerPageContract $itemsPerPage,
    ) {
    }

    public function __invoke(
        int $page,
        int $perPage,
        string $baseCurrencyCode,
        string $targetCurrency
    ): ?PaginationResultContract {
        return $this->paginator->paginate(
            $this->rateHistoryStorage->findByCurrencyPair(
                $this->currencyManager::create($baseCurrencyCode),
                $this->currencyManager::create($targetCurrency),
            ),
            $this->itemsPerPage->init($perPage),
            $page,
        );
    }
}
