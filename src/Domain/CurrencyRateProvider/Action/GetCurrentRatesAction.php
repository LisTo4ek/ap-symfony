<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Action;

use App\Domain\CurrencyRateProvider\Base\DTO\CurrentRatesDTO;
use App\Domain\CurrencyRateProvider\Service\RateFetcher;
use App\Domain\CurrencyRateProvider\Service\RateManager;
use DateTimeImmutable;

/**
 * Action to get current rates for today
 * Implements business logic from requirement 1.0-1.3
 */
class GetCurrentRatesAction
{
    public function __construct(
        private readonly RateManager $rateManager,
        private readonly RateFetcher $rateFetcher
    ) {
    }

    public function __invoke(): CurrentRatesDTO
    {
        $today = new DateTimeImmutable('today');
        $currentRates = $this->rateManager->getCurrentRates();

        // Check if we need to update rates
        $needsUpdate = $this->needsUpdate($currentRates, $today);

        if ($needsUpdate) {
            return $this->fetchAndUpdateRates($today, $currentRates);
        }

        return new CurrentRatesDTO(
            rates: $currentRates,
            date: $today,
            isActual: true,
            message: null
        );
    }

    private function needsUpdate(array $currentRates, DateTimeImmutable $today): bool
    {
        if (empty($currentRates)) {
            return true;
        }

        // Check if current rates are from today
        $firstRate = $currentRates[0] ?? null;
        if ($firstRate && $firstRate->getUpdatedAt()->format('Y-m-d') !== $today->format('Y-m-d')) {
            return true;
        }

        return false;
    }

    private function fetchAndUpdateRates(DateTimeImmutable $today, array $existingRates): CurrentRatesDTO
    {
        $rates = $this->rateFetcher->tryFetchRates($today);

        if ($rates === null && !empty($existingRates)) {
            return new CurrentRatesDTO(
                rates: $existingRates,
                date: $existingRates[0]->getUpdatedAt(),
                isActual: false,
                message: 'Курсы не актуальные. Не удалось загрузить актуальные данные с сайта ЦБР.'
            );
        }

        if ($rates === null && empty($existingRates)) {
            return new CurrentRatesDTO(
                rates: [],
                date: $today,
                isActual: false,
                message: 'Курсы ещё недоступны. Не удалось загрузить данные с сайта ЦБР.'
            );
        }

        $this->rateManager->saveRatesToHistory($rates);

        return new CurrentRatesDTO(
            rates: $this->rateManager->getCurrentRates(),
            date: $today,
            isActual: true,
            message: null
        );
    }
}

