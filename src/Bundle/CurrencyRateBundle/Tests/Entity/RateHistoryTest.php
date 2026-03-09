<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class RateHistoryTest extends TestCase
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
        $history = new RateHistory($this->rub, $this->usd, $value, $this->date);

        $this->assertSame('RUB', $history->getBaseCurrency()->getCode());
        $this->assertSame('USD', $history->getTargetCurrency()->getCode());
        $this->assertTrue($value->isEqualTo($history->getValue()));
        $this->assertSame('2026-03-06', $history->getDate()->format('Y-m-d'));
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);

        $this->assertNull($history->getId());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
        $after = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $history->getUpdatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $history->getUpdatedAt()->getTimestamp());
    }

    public function testSetBaseCurrencyReplacesCurrency(): void
    {
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
        $eur = new Currency('EUR');

        $result = $history->setBaseCurrency($eur);

        $this->assertSame('EUR', $history->getBaseCurrency()->getCode());
        $this->assertSame($history, $result);
    }

    public function testSetTargetCurrencyReplacesCurrency(): void
    {
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
        $gbp = new Currency('GBP');

        $result = $history->setTargetCurrency($gbp);

        $this->assertSame('GBP', $history->getTargetCurrency()->getCode());
        $this->assertSame($history, $result);
    }

    public function testSetValueReturnsSelf(): void
    {
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);

        $value = BigDecimal::of('99.99');
        $result = $history->setValue($value);

        $this->assertTrue($value->isEqualTo($history->getValue()));
        $this->assertSame($history, $result);
    }

    public function testSetDateReturnsSelf(): void
    {
        $history = new RateHistory($this->rub, $this->usd, BigDecimal::of('1'), $this->date);
        $newDate = new DateTimeImmutable('2020-01-01');

        $result = $history->setDate($newDate);

        $this->assertSame('2020-01-01', $history->getDate()->format('Y-m-d'));
        $this->assertSame($history, $result);
    }

    public function testSameCurrencyPairIsAllowed(): void
    {
        $history = new RateHistory($this->rub, $this->rub, BigDecimal::of('1'), $this->date);

        $this->assertSame('RUB', $history->getBaseCurrency()->getCode());
        $this->assertSame('RUB', $history->getTargetCurrency()->getCode());
    }

    public function testValueAcceptsHighPrecision(): void
    {
        $value = BigDecimal::of('0.0000000123456789');
        $history = new RateHistory($this->rub, $this->usd, $value, $this->date);

        $this->assertTrue($value->isEqualTo($history->getValue()));
    }
}
