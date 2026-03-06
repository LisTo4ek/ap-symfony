<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $rate = new CurrentRate($this->rub, $this->usd, '75.50', $this->date);

        $this->assertSame('RUB', $rate->getBaseCurrency()->getCode());
        $this->assertSame('USD', $rate->getTargetCurrency()->getCode());
        $this->assertSame('75.50', $rate->getValue());
        $this->assertSame('2026-03-06', $rate->getDate()->format('Y-m-d'));
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '1', $this->date);

        $this->assertNull($rate->getId());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $rate = new CurrentRate($this->rub, $this->usd, '1', $this->date);
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
        $rate = new CurrentRate($this->rub, $this->usd, '75.50', $this->date);
        $originalUpdatedAt = $rate->getUpdatedAt();

        // DateTimeImmutable resolution is seconds; force a tick
        usleep(1_100_000);

        $rate->setValue('80.00');

        $this->assertSame('80.00', $rate->getValue());
        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $rate->getUpdatedAt()->getTimestamp()
        );
    }

    public function testSetValueReturnsSelf(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '1', $this->date);

        $result = $rate->setValue('2');

        $this->assertSame($rate, $result);
    }

    public function testSetDateChangesDate(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '1', $this->date);
        $newDate = new DateTimeImmutable('2026-01-01');

        $rate->setDate($newDate);

        $this->assertSame('2026-01-01', $rate->getDate()->format('Y-m-d'));
    }

    public function testSetDateReturnsSelf(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '1', $this->date);

        $result = $rate->setDate(new DateTimeImmutable());

        $this->assertSame($rate, $result);
    }

    public function testValueAcceptsZero(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '0', $this->date);

        $this->assertSame('0', $rate->getValue());
    }

    public function testValueAcceptsNegative(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '-1.5', $this->date);

        $this->assertSame('-1.5', $rate->getValue());
    }

    public function testValueAcceptsHighPrecision(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '0.00000001', $this->date);

        $this->assertSame('0.00000001', $rate->getValue());
    }

    public function testValueAcceptsEmptyString(): void
    {
        $rate = new CurrentRate($this->rub, $this->usd, '', $this->date);

        $this->assertSame('', $rate->getValue());
    }
}
