<?php

namespace App\Tests\Trait;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;

trait CurrencyTrait
{
    protected CurrencyManagerContract $currencyManager;
    protected CurrencyContract $rubCurrency;
    protected CurrencyContract $usdCurrency;
    protected CurrencyContract $eurCurrency;

    protected function initCurrencies(): void
    {
        $this->currencyManager = $this->fromContainer(CurrencyManagerContract::class);
        $this->rubCurrency = $this->currencyManager->create(CurrencyIso4217Enum::RUB->value);
        $this->usdCurrency = $this->currencyManager->create(CurrencyIso4217Enum::USD->value);
        $this->eurCurrency = $this->currencyManager->create(CurrencyIso4217Enum::EUR->value);
    }
}
