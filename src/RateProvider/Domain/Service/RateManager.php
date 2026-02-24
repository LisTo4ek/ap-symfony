<?php

namespace App\RateProvider\Domain\Service;

use App\RateProvider\Domain\Entity\Rate;
use App\RateProvider\Domain\ValueObject\Currency;
use App\Entity\CurrentRate;
use App\Entity\RateHistory;
use App\Repository\CurrentRateRepository;
use App\Repository\RateHistoryRepository;
use App\Event\RateSavedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Domain service for managing currency rates
 */
class RateManager
{
    public function __construct(
        private readonly CurrentRateRepository $currentRateRepository,
        private readonly RateHistoryRepository $rateHistoryRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Save rates to history and dispatch events
     * @param Rate[] $rates
     */
    public function saveRatesToHistory(array $rates): void
    {
        foreach ($rates as $rate) {
            $historyEntity = $this->rateHistoryRepository->updateOrCreate(
                $rate->targetCurrency,
                (string) $rate->value,
                $rate->date
            );

            $this->rateHistoryRepository->save($historyEntity, false);

            // Dispatch event for current rate update
            $this->eventDispatcher->dispatch(new RateSavedEvent($rate));
        }

        // Flush all at once for better performance
        $this->rateHistoryRepository->save(
            $historyEntity ?? null,
            true
        );
    }

    /**
     * Get current rates from database
     * @return CurrentRate[]
     */
    public function getCurrentRates(): array
    {
        return $this->currentRateRepository->findAll();
    }

    /**
     * Check if current rates table is empty
     */
    public function hasCurrentRates(): bool
    {
        return count($this->getCurrentRates()) > 0;
    }

    /**
     * Get rate history for a specific target currency
     * @return RateHistory[]
     */
    public function getRateHistoryByTargetCurrency(
        Currency $targetCurrency,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array
    {
        if (!$from || !$to) {
            return [];
        }

        return $this->rateHistoryRepository->findByCharCodeAndDateRange(
            $targetCurrency,
            $from,
            $to
        );
    }

    /**
     * Get rate history for a specific base currency
     * @return RateHistory[]
     */
    public function getRateHistoryByBaseCurrency(
        Currency $baseCurrency,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): array
    {
        if (!$from || !$to) {
            return [];
        }

        // Query by base currency
        $qb = $this->rateHistoryRepository->createQueryBuilder('rh');

        return $qb
            ->andWhere('rh.baseCurrency = :baseCurrency')
            ->andWhere('rh.date BETWEEN :from AND :to')
            ->setParameter('baseCurrency', $baseCurrency)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('rh.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

