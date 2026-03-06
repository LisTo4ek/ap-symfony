<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Helper;

use DateTimeInterface;

class DateCompare
{
    public static function eq(DateTimeInterface $date1, DateTimeInterface $date2)
    {
        return $date1->format('Y-m-d') === $date2->format('Y-m-d');
    }
}

