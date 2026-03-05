<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Domain\Enum\CurrencyEnum;
use Money\Currency;

trait CurrencyTrait
{
    protected Currency $rubCurrency;
    protected Currency $usdCurrency;
    protected Currency $eurCurrency;

    protected function initCurrencies(): void
    {
        $this->rubCurrency = new Currency(CurrencyEnum::RUB->value);
        $this->usdCurrency = new Currency(CurrencyEnum::USD->value);
        $this->eurCurrency = new Currency(CurrencyEnum::EUR->value);
    }
}
