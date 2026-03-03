<?php

namespace App\Bundle\CurrencyRateProviderBundle\Src\Helper;

class NumberHelper
{
    public static function normalize(string $number): string
    {
        $res = \str_replace(',', '.', $number);

        if (\mb_stripos($res, 'e') !== false) {
            $res = \sprintf('%.50f', $res);
        }

        $res = rtrim($res, '0');
        $res = rtrim($res, '.');

        return $res;
    }
}
