<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class BigDecimalStringType extends StringType
{
    public const string NAME = 'big_decimal_string';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BigDecimal
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a non-empty string');
        }

        return BigDecimal::of($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof BigDecimal) {
            throw new \InvalidArgumentException('Value must be an instance of BigDecimal');
        }

        return $value->strippedOfTrailingZeros()->toString();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
