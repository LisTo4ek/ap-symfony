<?php

declare(strict_types=1);

namespace App\Domain\Contracts\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Domain\Contracts\Pagination\PageableContract;
use App\Entity\CurrentRate;
use DateTimeImmutable;
use DateTimeInterface;

interface CurrentRateStorageContract
{
    public function upsertForCurrencyPair(
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
        string $value,
        DateTimeInterface $date,
    ): CurrentRate;

    public function hasRecordsByDay(DateTimeImmutable $date): bool;

    public function getLatestDate(): ?DateTimeImmutable;

    public function findByDateAndBaseCurrency(DateTimeImmutable $date, CurrencyContract $baseCurrency): PageableContract;
}

