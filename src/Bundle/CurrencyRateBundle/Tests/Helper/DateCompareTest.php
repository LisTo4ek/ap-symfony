<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Helper;

use App\Bundle\CurrencyRateBundle\Src\Helper\DateCompare;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateCompareTest extends TestCase
{
    public function testEqReturnsTrueForSameDate(): void
    {
        $d1 = new DateTimeImmutable('2026-03-06 10:00:00');
        $d2 = new DateTimeImmutable('2026-03-06 23:59:59');

        $this->assertTrue(DateCompare::eq($d1, $d2));
    }

    public function testEqReturnsFalseForDifferentDates(): void
    {
        $d1 = new DateTimeImmutable('2026-03-05 23:59:59');
        $d2 = new DateTimeImmutable('2026-03-06 00:00:00');

        $this->assertFalse(DateCompare::eq($d1, $d2));
    }

    public function testEqIgnoresTime(): void
    {
        $d1 = new DateTimeImmutable('2026-01-01 00:00:00');
        $d2 = new DateTimeImmutable('2026-01-01 12:30:45');

        $this->assertTrue(DateCompare::eq($d1, $d2));
    }

    public function testEqWithDifferentTimezoneObjects(): void
    {
        // Both have the same Y-m-d even though constructed differently
        $d1 = new DateTimeImmutable('2026-03-06');
        $d2 = DateTimeImmutable::createFromFormat('d.m.Y', '06.03.2026');

        $this->assertInstanceOf(DateTimeImmutable::class, $d2);
        $this->assertTrue(DateCompare::eq($d1, $d2));
    }

    public function testEqWithYearBoundary(): void
    {
        $d1 = new DateTimeImmutable('2025-12-31');
        $d2 = new DateTimeImmutable('2026-01-01');

        $this->assertFalse(DateCompare::eq($d1, $d2));
    }

    public function testEqSameObjectReturnsTrue(): void
    {
        $d = new DateTimeImmutable('2026-06-15');

        $this->assertTrue(DateCompare::eq($d, $d));
    }
}
