<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Config;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after a currency rate has been saved to the rate history storage.
 *
 * Carries the saved RateContainer so listeners (e.g. current-rate sync)
 * can react to newly persisted rate data.
 */
class CurrencyRateSavedEvent extends Event
{
    /**
     * @param RateContainer $rate The rate data that was just saved
     */
    public function __construct(
        private readonly RateContainer $rate
    ) {
    }

    /**
     * Returns the saved rate data associated with this event.
     *
     * @return RateContainer The rate container with currency pair, value, and date
     */
    public function getRate(): RateContainer
    {
        return $this->rate;
    }
}
