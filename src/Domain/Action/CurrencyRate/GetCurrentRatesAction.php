<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\Contracts\Pagination\ItemsPerPageContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorContract;
use App\Domain\Pagination\ItemsPerPageDefault;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GetCurrentRatesAction
{
    public function __construct(
        private readonly PaginatorContract $paginator,
        private readonly CurrentRateStorageContract $currentRateStorage,
        private readonly CurrencyManagerContract $currencyManager,
        #[Autowire(service: ItemsPerPageDefault::class)]
        private readonly ItemsPerPageContract $itemsPerPage,
    ) {
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param string $baseCurrencyCode
     * @return array{0: DateTimeImmutable|null, 1: PaginationResultContract|null}
     */
    public function __invoke(int $page, int $perPage, string $baseCurrencyCode): array
    {
        $latestDate = $this->currentRateStorage->getLatestDate();

        return [
            $latestDate,
            $latestDate
                ? $this->paginator->paginate(
                    $this->currentRateStorage->findByDateAndBaseCurrency(
                        $latestDate,
                        $this->currencyManager::create($baseCurrencyCode)
                    ),
                    $this->itemsPerPage->init($perPage),
                    $page,
                )
                : null
        ];
    }
}
