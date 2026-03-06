<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Config;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use Symfony\Contracts\EventDispatcher\Event;

class CurrencyRateSavedEvent extends Event
{
    public function __construct(
        private readonly RateContainer $rate
    ) {
    }

    public function getRate(): RateContainer
    {
        return $this->rate;
    }
}

