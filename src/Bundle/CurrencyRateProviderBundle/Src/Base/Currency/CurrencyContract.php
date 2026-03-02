<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency;

use Stringable;

interface CurrencyContract extends Stringable {
    public function getCode(): string;
}
