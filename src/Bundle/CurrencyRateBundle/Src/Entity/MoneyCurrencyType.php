<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;
use Money\Currency;

/**
 * Custom Doctrine DBAL type that maps Money\Currency objects to a CHAR(3) database column.
 *
 * Stores the ISO 4217 currency code string in the database and converts it
 * back to a Money\Currency instance when reading.
 */
class MoneyCurrencyType extends StringType
{
    /** @var string DBAL type name used in column mapping annotations */
    public const string NAME = 'money_currency';

    /**
     * Converts a database string value to a Money\Currency PHP object.
     *
     * @param mixed            $value    The raw database value (3-letter currency code string or null)
     * @param AbstractPlatform $platform The database platform
     *
     * @return Currency|null The Currency instance, or null if the value is null
     *
     * @throws InvalidArgumentException If the value is not a non-empty string
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Currency
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Currency code must be a non-empty string');
        }

        return new Currency($value);
    }

    /**
     * Converts a Money\Currency object or string to a string for database storage.
     *
     * Accepts both Currency instances (extracts the code) and plain strings.
     *
     * @param mixed            $value    The Currency instance, currency code string, or null
     * @param AbstractPlatform $platform The database platform
     *
     * @return string|null The 3-letter currency code, or null if the value is null
     *
     * @throws InvalidArgumentException If the value is not a string or Currency instance
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value->getCode();
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a string or Currency instance');
        }

        return $value;
    }

    /**
     * Returns the DBAL type name.
     *
     * @return string The type name 'money_currency'
     */
    public function getName(): string
    {
        return self::NAME;
    }
}
