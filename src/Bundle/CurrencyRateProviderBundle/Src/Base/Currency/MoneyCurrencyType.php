<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Money\Currency;

class MoneyCurrencyType extends StringType
{
    public const string NAME = 'money_currency';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Currency
    {
        if ($value === null) {
            return null;
        }

        return new Currency($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value->getCode();
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
