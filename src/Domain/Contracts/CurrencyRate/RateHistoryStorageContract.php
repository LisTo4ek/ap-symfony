<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Domain\Contracts\Pagination\PageableContract;
use App\Entity\RateHistory;
use Money\Currency;

interface RateHistoryStorageContract
{
    /**
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;

    public function findByCurrencyPair(Currency $baseCurrency, Currency $targetCurrency): PageableContract;
}
