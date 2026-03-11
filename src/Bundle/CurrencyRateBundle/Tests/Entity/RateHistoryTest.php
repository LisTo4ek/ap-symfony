<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RateHistoryTest extends TestCase
{
    use CurrencyTrait;

    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->date = new DateTimeImmutable('2026-03-06');
    }

    public function testConstructorSetsAllProperties(): void
    {
        $value = BigDecimal::of('75.50');
        $history = new RateHistory(self::getRub(), self::getUsd(), $value, $this->date);
        $this->assertSame(self::getRub(), $history->getBaseCurrency());
        $this->assertSame(self::getUsd(), $history->getTargetCurrency());
        $this->assertSame($value->toString(), $history->getValue()->toString());
        $this->assertSame('2026-03-06', $history->getDate()->format('Y-m-d'));
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $history = new RateHistory(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);
        $this->assertNull($history->getId());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $history = new RateHistory(self::getRub(), self::getUsd(), BigDecimal::of('1'), $this->date);
        $after = new DateTimeImmutable();
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $history->getUpdatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $history->getUpdatedAt()->getTimestamp());
    }

    public function testSameCurrencyPairIsAllowed(): void
    {
        $history = new RateHistory(self::getRub(), self::getRub(), BigDecimal::of('1'), $this->date);
        $this->assertSame(self::getRub(), $history->getBaseCurrency());
        $this->assertSame(self::getRub(), $history->getTargetCurrency());
    }

    public function testValueAcceptsHighPrecision(): void
    {
        $value = BigDecimal::of('0.0000000123456789');
        $history = new RateHistory(self::getRub(), self::getUsd(), $value, $this->date);
        $this->assertSame($value->toString(), $history->getValue()->toString());
    }
}
