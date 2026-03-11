<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTimeInterface;

/**
 * Utility class for date-only comparisons (ignoring time components).
 */
class DateCompareService
{
    /**
     * Checks whether two dates represent the same calendar day (Y-m-d).
     *
     * Compares only the date portion, ignoring hours, minutes, seconds, and timezone.
     *
     * @param DateTimeInterface $date1 The first date to compare
     * @param DateTimeInterface $date2 The second date to compare
     *
     * @return bool True if both dates fall on the same calendar day
     */
    public static function eq(DateTimeInterface $date1, DateTimeInterface $date2): bool
    {
        return $date1->format('Y-m-d') === $date2->format('Y-m-d');
    }
}
