<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency;

interface CurrencyManagerContract
{
    public static function isValid(string $code): bool;
    public static function create(string $code): CurrencyContract;
}
