<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CurrentRateTest extends TestCase
{
    use CurrencyTrait;

    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->date = new DateTimeImmutable('2026-03-06');
    }

    public function testConstructorSetsAllProperties(): void
    {
        $value = BigDecimal::of('75.50');
        $rate = new CurrentRate(self::getRub(), self::getUsd(), $value, $this->date);

        $this->assertSame(self::getRub(), $rate->getBaseCurrency());
        $this->assertSame(self::getUsd(), $rate->getTargetCurrency());
        $this->assertSame($value->toString(), $rate->getValue()->toString());
        $this->assertSame('2026-03-06', $rate->getDate()->format('Y-m-d'));
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $rate = new CurrentRate(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);

        $this->assertNull($rate->getId());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $rate = new CurrentRate(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);
        $after = new DateTimeImmutable();
        $this->assertGreaterThanOrEqual(
            $before->getTimestamp(),
            $rate->getUpdatedAt()->getTimestamp()
        );
        $this->assertLessThanOrEqual(
            $after->getTimestamp(),
            $rate->getUpdatedAt()->getTimestamp()
        );
    }

    public function testSetValueUpdatesValueAndUpdatedAt(): void
    {
        $value1 = BigDecimal::of('75.50');
        $value2 = BigDecimal::of('80.00');
        $rate = new CurrentRate(self::getRub(), self::getUsd(), $value1, $this->date);
        $originalUpdatedAt = $rate->getUpdatedAt();
        usleep(1_100_000);
        $rate->setValue($value2);
        $this->assertSame($value2->toString(), $rate->getValue()->toString());
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $rate->getUpdatedAt()->getTimestamp()
        );
    }

    public function testSetValueReturnsSelf(): void
    {
        $rate = new CurrentRate(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);

        $result = $rate->setValue(BigDecimal::of('2'));

        $this->assertSame($rate, $result);
    }

    public function testSetDateChangesDate(): void
    {
        $rate = new CurrentRate(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);
        $newDate = new DateTimeImmutable('2026-01-01');

        $rate->setDate($newDate);

        $this->assertSame($newDate->format('Y-m-d'), $rate->getDate()->format('Y-m-d'));
    }

    public function testSetDateReturnsSelf(): void
    {
        $rate = new CurrentRate(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);

        $result = $rate->setDate(new DateTimeImmutable());

        $this->assertSame($rate, $result);
    }

    public function testValueAcceptsZero(): void
    {
        $value = BigDecimal::of('0');
        $rate = new CurrentRate(self::getRub(), self::getUsd(), $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }

    public function testValueAcceptsNegative(): void
    {
        $value = BigDecimal::of('-1.5');
        $rate = new CurrentRate(self::getRub(), self::getUsd(), $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }

    public function testValueAcceptsHighPrecision(): void
    {
        $value = BigDecimal::of('0.00000001');
        $rate = new CurrentRate(self::getRub(), self::getUsd(), $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }
}
