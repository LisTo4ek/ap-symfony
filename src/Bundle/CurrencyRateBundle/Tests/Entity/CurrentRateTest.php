<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class CurrentRateTest extends TestCase
{
    private Currency $rub;
    private Currency $usd;
    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->rub = new Currency('RUB');
        $this->usd = new Currency('USD');
        $this->date = new DateTimeImmutable('2026-03-06');
    }

    public function testConstructorSetsAllProperties(): void
    {
        $value = BigDecimal::of('75.50');
        $rate = new CurrentRate($this->rub, $this->usd, $value, $this->date);

        $this->assertSame('RUB', $rate->getBaseCurrency()->getCode());
        $this->assertSame('USD', $rate->getTargetCurrency()->getCode());
        $this->assertSame($value->toString(), $rate->getValue()->toString());
        $this->assertSame('2026-03-06', $rate->getDate()->format('Y-m-d'));
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $value = BigDecimal::of('1');
        $rate = new CurrentRate($this->rub, $this->usd, BigDecimal::of('1'), $this->date);

        $this->assertNull($rate->getId());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $rate = new CurrentRate($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
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

        $rate = new CurrentRate($this->rub, $this->usd, $value1, $this->date);
        $originalUpdatedAt = $rate->getUpdatedAt();

        // DateTimeImmutable resolution is seconds; force a tick
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
        $rate = new CurrentRate($this->rub, $this->usd, BigDecimal::of('1'), $this->date);

        $result = $rate->setValue(BigDecimal::of('2'));

        $this->assertSame($rate, $result);
    }

    public function testSetDateChangesDate(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
        $newDate = new DateTimeImmutable('2026-01-01');

        $rate->setDate($newDate);

        $this->assertSame('2026-01-01', $rate->getDate()->format('Y-m-d'));
    }

    public function testSetDateReturnsSelf(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, BigDecimal::of('1'), $this->date);

        $result = $rate->setDate(new DateTimeImmutable());

        $this->assertSame($rate, $result);
    }

    public function testValueAcceptsZero(): void
    {
        $value = BigDecimal::of('0');
        $rate = new CurrentRate($this->rub, $this->usd, $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }

    public function testValueAcceptsNegative(): void
    {
        $value = BigDecimal::of('-1.5');
        $rate = new CurrentRate($this->rub, $this->usd, $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }

    public function testValueAcceptsHighPrecision(): void
    {
        $value = BigDecimal::of('0.00000001');
        $rate = new CurrentRate($this->rub, $this->usd, $value, $this->date);

        $this->assertSame($value->toString(), $rate->getValue()->toString());
    }

//    public function testValueFailsOnEmptyString(): void
//    {
//        $value = BigDecimal::of('');
//        $rate = new CurrentRate($this->rub, $this->usd, $value, $this->date);
//
//        $this->assertSame($value->toString(), $rate->getValue()->toString());
//    }
}
