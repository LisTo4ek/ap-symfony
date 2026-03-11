<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\EventListener;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Service\DateCompareService;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that synchronizes today's rate history records to the current_rate table.
 *
 * Listens for CurrencyRateSavedEvent and, when the saved rate's date matches today,
 * upserts the corresponding current rate entry so that the current_rate table always
 * reflects the latest known rate for each currency pair.
 *
 * @property CurrentRateStorageInterface $storage Storage for upserting current rate records
 */
class CurrentCurrencyRateSyncListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CurrentRateStorageInterface $storage,
    ) {
    }

    /**
     * Returns the events this subscriber listens to.
     *
     * @return array<string, string> Map of event class to handler method name
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CurrencyRateSavedEvent::class => 'onRateSaved',
        ];
    }

    /**
     * Handles CurrencyRateSavedEvent by upserting the rate into the current_rate table if the date is today.
     *
     * Skips the upsert if the rate's date does not match today's date.
     *
     * @param CurrencyRateSavedEvent $event The event carrying the saved rate data
     * @todo: set cache for current rates by date
     */
    public function onRateSaved(CurrencyRateSavedEvent $event): void
    {
        $rate = $event->getRate();
        $today = new DateTimeImmutable('today');
        if (!DateCompareService::eq($rate->date, $today)) {
            return;
        }

        $this->storage->upsertForCurrencyPair(
            $rate->baseCurrency,
            $rate->targetCurrency,
            $rate->rate,
            $rate->date,
        );
    }
}
