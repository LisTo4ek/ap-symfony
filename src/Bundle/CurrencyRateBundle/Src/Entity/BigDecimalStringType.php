<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

/**
 * Custom Doctrine DBAL type that maps Brick\Math\BigDecimal to a database string column.
 *
 * Stores BigDecimal values as stripped-of-trailing-zeros strings in the database
 * and converts them back to BigDecimal instances when reading.
 */
class BigDecimalStringType extends StringType
{
    /** @var string DBAL type name used in column mapping annotations */
    public const string NAME = 'big_decimal_string';

    /**
     * Converts a database string value to a BigDecimal PHP object.
     *
     * @param mixed $value The raw database value (string or null)
     * @param AbstractPlatform $platform The database platform
     *
     * @return BigDecimal|null The BigDecimal instance, or null if the value is null
     *
     * @throws InvalidArgumentException If the value is not a string
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BigDecimal
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be a non-empty string');
        }

        return BigDecimal::of($value);
    }

    /**
     * Converts a BigDecimal PHP object to a string for database storage.
     *
     * Trailing zeros are stripped before persisting.
     *
     * @param mixed $value The BigDecimal instance or null
     * @param AbstractPlatform $platform The database platform
     *
     * @return string|null The string representation, or null if the value is null
     *
     * @throws InvalidArgumentException If the value is not a BigDecimal instance
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof BigDecimal) {
            throw new InvalidArgumentException('Value must be an instance of BigDecimal');
        }

        return $value->strippedOfTrailingZeros()->toString();
    }

    /**
     * Returns the DBAL type name.
     *
     * @return string The type name 'big_decimal_string'
     */
    public function getName(): string
    {
        return self::NAME;
    }
}
