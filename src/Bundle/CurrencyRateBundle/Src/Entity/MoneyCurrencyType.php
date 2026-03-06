<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

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

        if (!is_string($value) || $value === '') {
            throw new \InvalidArgumentException('Currency code must be a non-empty string');
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

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string or Currency instance');
        }

        return $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
