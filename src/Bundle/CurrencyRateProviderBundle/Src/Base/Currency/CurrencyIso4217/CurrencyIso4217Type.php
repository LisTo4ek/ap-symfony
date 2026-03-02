<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class CurrencyIso4217Type extends StringType
{
    public const string NAME = 'currency_iso_4217';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CurrencyContract
    {
        if ($value === null) {
            return null;
        }

        return CurrencyIso4217Manager::create($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CurrencyContract) {
            return $value->getCode();
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
