<?php

namespace App\Domain\Helper;

class NumberHelper
{
    public static function normalize(string $number): string
    {
        $res = \str_replace(',', '.', $number);

        // Handle scientific notation by converting to float first, then to string
        if (mb_stripos($res, 'e') !== false) {
//            return (string) (float) $res;
            $res = sprintf('%.50f', $res);
            // Remove trailing zeros and the decimal point if it becomes an integer (optional, but clean)
            $res = rtrim($res, '0');
            $res = rtrim($res, '.');
        }

        return $res;

//        $number = sprintf('%.50f', $number);
//        // Remove trailing zeros and the decimal point if it becomes an integer (optional, but clean)
//        $number = rtrim($number, '0');
//        $number = rtrim($number, '.');
//        return $number;
    }
}
