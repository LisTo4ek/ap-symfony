<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\RateSavedEvent;
use App\Repository\CurrentRateRepository;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurrencySyncSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentRateRepository $repository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RateSavedEvent::class => 'onRateSaved',
        ];
    }

    public function onRateSaved(RateSavedEvent $event): void
    {
        $rate = $event->getRate();
        $today = (new DateTimeImmutable())->format('Y-m-d');

        // Only update current course if the rate is for today
        if ($rate->date->format('Y-m-d') === $today) {
            $this->repository->upsertForCurrencyPair(
                $rate->baseCurrency,
                $rate->targetCurrency,
                $rate->rate,
            );

            $currentRate = $this->repository->findByCurrencyPair(
                $rate->baseCurrency,
                $rate->targetCurrency
            );

            if ($currentRate) {
                $this->repository->save($currentRate, true);
            }
        }
    }
}
