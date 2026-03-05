<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Domain\Contracts\Pagination\PageableContract;
use App\Entity\CurrentRate;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;

interface CurrentRateStorageContract
{
    public function upsertForCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
        string $value,
        DateTimeInterface $date,
    ): CurrentRate;

    public function hasRecordsByDay(DateTimeImmutable $date): bool;

    public function getLatestDate(): ?DateTimeImmutable;

    public function findByDateAndBaseCurrency(DateTimeImmutable $date, Currency $baseCurrency): PageableContract;
}
