<?php

declare(strict_types=1);

namespace App\Event;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use Symfony\Contracts\EventDispatcher\Event;

class RateSavedEvent extends Event
{
    public function __construct(
        private readonly Rate $rate
    ) {
    }

    public function getRate(): Rate
    {
        return $this->rate;
    }
}

