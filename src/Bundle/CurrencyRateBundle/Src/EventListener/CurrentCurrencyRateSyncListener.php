<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\EventListener;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Helper\DateCompare;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurrentCurrencyRateSyncListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentRateStorageInterface $storage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CurrencyRateSavedEvent::class => 'onRateSaved',
        ];
    }

    public function onRateSaved(CurrencyRateSavedEvent $event): void
    {
        $rate = $event->getRate();
        $today = new DateTimeImmutable('today');

        if (!DateCompare::eq($rate->date, $today)) {
            return;
        }

        $this->storage->upsertForCurrencyPair(
            $rate->baseCurrency,
            $rate->targetCurrency,
            $rate->rate,
            $rate->date,
        );

        // todo: set cache for current rates by date
    }
}
