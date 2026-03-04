<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Domain\Contracts\Pagination\PageableContract;
use App\Entity\RateHistory;

interface RateHistoryStorageContract
{
    /**
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;

    public function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): PageableContract;
}
