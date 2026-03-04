<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Entity\RateHistory;

interface RateHistoryStorageContract
{
    /**
     * @param array<RateHistory> $entities
     */
    public function saveBatch(array $entities): void;
}

