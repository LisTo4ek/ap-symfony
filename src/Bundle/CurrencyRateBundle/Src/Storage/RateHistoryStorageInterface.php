<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use Money\Currency;

interface RateHistoryStorageInterface
{
    /**
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;

    /**
     * @return PaginationPageableServiceInterface<RateHistory>
     */
    public function findByCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
    ): PaginationPageableServiceInterface;
}
