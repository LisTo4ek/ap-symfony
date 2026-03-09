<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class RateContainerTest extends TestCase
{
    public function testPropertiesArePublicAndReadable(): void
    {
        $base = new Currency('RUB');
        $target = new Currency('USD');
        $date = new DateTimeImmutable('2026-03-06');
        $value = BigDecimal::of('75.50');
        $c = new RateContainer($base, $target, $value, $date);
        $this->assertSame($base, $c->baseCurrency);
        $this->assertSame($target, $c->targetCurrency);
        $this->assertTrue($c->rate->isEqualTo($value));
        $this->assertSame($date, $c->date);
    }

    public function testSameCurrencyPairIsAllowed(): void
    {
        $rub = new Currency('RUB');
        $c = new RateContainer($rub, $rub, BigDecimal::of('1'), new DateTimeImmutable());
        $this->assertSame('RUB', $c->baseCurrency->getCode());
        $this->assertSame('RUB', $c->targetCurrency->getCode());
    }

    public function testEmptyRateStringIsAllowed(): void
    {
        $c = new RateContainer(
            new Currency('RUB'),
            new Currency('USD'),
            BigDecimal::of('0'),
            new DateTimeImmutable()
        );
        $this->assertTrue($c->rate->isEqualTo(BigDecimal::of('0')));
    }

    public function testNegativeRateIsAllowed(): void
    {
        $value = BigDecimal::of('-0.5');
        $c = new RateContainer(
            new Currency('RUB'),
            new Currency('USD'),
            $value,
            new DateTimeImmutable()
        );
        $this->assertTrue($c->rate->isEqualTo($value));
    }
}
