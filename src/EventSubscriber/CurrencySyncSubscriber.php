<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\Contracts\CurrentRateStorageContract;
use App\Event\RateSavedEvent;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurrencySyncSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentRateStorageContract $storage
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
        $today = new DateTimeImmutable();

        if ($rate->date->diff($today)->days === 0) {
            $this->storage->upsertForCurrencyPair(
                $rate->baseCurrency,
                $rate->targetCurrency,
                $rate->rate,
                $rate->date,
            );

            $currentRate = $this->storage->findByCurrencyPair(
                $rate->baseCurrency,
                $rate->targetCurrency,
            );

            if ($currentRate) {
                $this->storage->save($currentRate, true);
            }
        }
    }
}
