<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency;

class Currency implements CurrencyContract
{
    public function __construct(
        private string $code,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
