<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Bundle\CurrencyRateProviderBundle\Src\Helper\DateCompare;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Event\RateSavedEvent;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurrencySyncSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentRateStorageContract $storage,
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
