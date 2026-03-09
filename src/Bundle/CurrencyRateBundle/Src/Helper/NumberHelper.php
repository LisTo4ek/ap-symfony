<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Helper;

use Brick\Math\BigDecimal;

use function str_replace;

class NumberHelper
{
    /**
     * @param string $number
     * @return BigDecimal
     */
    public static function normalize(string $number): BigDecimal
    {
        $res = str_replace(',', '.', $number);

        return BigDecimal::of($res)->strippedOfTrailingZeros();
    }
}
