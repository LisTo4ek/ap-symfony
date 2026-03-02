<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Entity\CurrentRate;
use DateTimeImmutable;
use DateTimeInterface;

interface CurrentRateStorageContract
{
    public function save(CurrentRate $entity, bool $flush = false): void;

    public function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): ?CurrentRate;
    public function upsertForCurrencyPair(
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
        string $value,
        DateTimeInterface $date,
    ): CurrentRate;

    /**
     * @return array<CurrentRate>
     */
    public function findAll(): array;

    /**
     * @return array<CurrentRate>
     */
    public function findByBaseCurrency(CurrencyContract $baseCurrency): array;

    /**
     * @return array<CurrentRate>
     */
    public function findByTargetCurrency(CurrencyContract $targetCurrency): array;

    /**
     * @return array<CurrentRate>
     */
    public function getTodayRecords(): array;

    public function hasRecordsByDay(DateTimeImmutable $date): bool;
}

