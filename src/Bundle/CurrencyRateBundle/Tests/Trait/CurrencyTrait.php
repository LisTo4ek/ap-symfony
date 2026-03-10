<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Trait;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use Money\Currency;

trait CurrencyTrait
{
    protected static Currency $rubCurrency;
    protected static Currency $usdCurrency;
    protected static Currency $eurCurrency;
    protected static Currency $gbpCurrency;
    protected static Currency $jpyCurrency;

    public static function getEur(): Currency
    {
        return self::$eurCurrency ??= new Currency(CurrencyEnum::EUR->value);
    }

    public static function getRub(): Currency
    {
        return self::$rubCurrency ??= new Currency(CurrencyEnum::RUB->value);
    }

    public static function getUsd(): Currency
    {
        return self::$usdCurrency ??= new Currency(CurrencyEnum::USD->value);
    }

    public static function getGbp(): Currency
    {
        return self::$gbpCurrency ??= new Currency(CurrencyEnum::GBP->value);
    }

    public static function getJpy(): Currency
    {
        return self::$jpyCurrency ??= new Currency(CurrencyEnum::JPY->value);
    }
}
