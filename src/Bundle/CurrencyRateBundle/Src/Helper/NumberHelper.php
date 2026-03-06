<?php

namespace App\Bundle\CurrencyRateBundle\Src\Helper;

use function mb_stripos;
use function rtrim;
use function sprintf;
use function str_replace;

class NumberHelper
{
    public static function normalize(string $number): string
    {
        $res = str_replace(',', '.', $number);

        if (mb_stripos($res, 'e') !== false) {
            $res = sprintf('%.50f', $res);
        }

        $res = rtrim($res, '0');
        $res = rtrim($res, '.');

        return $res;
    }
}
