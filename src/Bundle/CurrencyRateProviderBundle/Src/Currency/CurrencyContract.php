<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Currency;

interface CurrencyContract {
    public function getCode(): string;
}
