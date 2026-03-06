<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;

interface CurrentRateStorageInterface
{
    public function upsertForCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
        string $value,
        DateTimeInterface $date,
    ): CurrentRate;

    public function hasRecordsByDay(DateTimeImmutable $date): bool;

    public function getLatestDate(): ?DateTimeImmutable;

    /**
     * @return PaginationPageableServiceInterface<CurrentRate>
     */
    public function findByDateAndBaseCurrency(
        DateTimeImmutable $date,
        Currency $baseCurrency,
    ): PaginationPageableServiceInterface;
}
