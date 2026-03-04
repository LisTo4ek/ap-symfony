<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\Currency;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use function array_all;
use function array_map;
use function in_array;

#[AsAlias(CurrencyManagerContract::class)]
class CurrencyIso4217Manager implements CurrencyManagerContract
{
    public static function isValid(string $code): bool
    {
        return in_array($code, array_map(static fn(CurrencyIso4217Enum $case) => $case->value, CurrencyIso4217Enum::cases()), true);
    }

    /**
     * @param array<string> $codes
     */
    public static function isValidArray(array $codes): bool
    {
        return array_all($codes, static fn($code) => self::isValid($code));
    }

    public static function create(string $code): CurrencyContract
    {
        return new Currency(CurrencyIso4217Enum::from($code)?->value);
    }
}
