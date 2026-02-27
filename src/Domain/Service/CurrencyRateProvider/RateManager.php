<?php

declare(strict_types=1);

namespace App\Domain\Service\CurrencyRateProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Entity\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;
use App\Entity\CurrentRate;
use App\Entity\RateHistory;
use App\Event\RateSavedEvent;
use App\Repository\CurrentRateRepository;
use App\Repository\RateHistoryRepository;
use DateTimeInterface;
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
     * @param array<Rate> $rates
     */
    public function saveRatesToHistory(array $rates): void
    {
        foreach ($rates as $rate) {
            $historyEntity = $this->rateHistoryRepository->updateOrCreate(
                $rate->baseCurrency,
                $rate->targetCurrency,
                (string) $rate->rate,
                $rate->date
            );

            $this->rateHistoryRepository->save($historyEntity);

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
     * @return array<CurrentRate>
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
     * @return array<RateHistory>
     */
    public function getRateHistoryByTargetCurrency(
        CurrencyContract $baseCurrency,
        ?CurrencyContract $targetCurrency,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): array {
        if (!$from || !$to) {
            return [];
        }

        return $this->rateHistoryRepository->findByCurrencyPairAndDateRange(
            $baseCurrency,
            $targetCurrency,
            $from,
            $to
        );
    }

    /**
     * Get rate history for a specific base currency
     * @return array<RateHistory>
     */
    public function getRateHistoryByCurrency(
        CurrencyContract $baseCurrency,
        ?CurrencyContract $targetCurrency,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): array {
        if (!$from || !$to) {
            return [];
        }

        // Query by base currency
        $qb = $this->rateHistoryRepository->createQueryBuilder('rh');

        if ($targetCurrency) {
            $qb->andWhere('rh.targetCurrency = :targetCurrency')
               ->setParameter('targetCurrency', $targetCurrency->getCode());
        }

        return $qb
            ->andWhere('rh.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency->getCode())
            ->andWhere('rh.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('rh.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

