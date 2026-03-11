<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use Brick\Math\BigDecimal;

use function str_replace;

/**
 * Utility class for normalizing numeric string representations.
 */
class NumberService
{
    /**
     * Normalizes a numeric string by replacing comma decimal separators with dots
     * and converting it to a BigDecimal with trailing zeros stripped.
     *
     * Useful for parsing locale-dependent number formats (e.g. CBR XML uses commas).
     *
     * @param string $number The raw numeric string (may contain commas as decimal separators)
     *
     * @return BigDecimal The normalized BigDecimal value with trailing zeros removed
     */
    public static function normalize(string $number): BigDecimal
    {
        $res = str_replace(',', '.', $number);

        return BigDecimal::of($res)->strippedOfTrailingZeros();
    }
}
