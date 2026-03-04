<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Entity\RateHistory;
use Doctrine\ORM\QueryBuilder;

interface RateHistoryStorageContract
{
    /**
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;

    public function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): QueryBuilder;
}

