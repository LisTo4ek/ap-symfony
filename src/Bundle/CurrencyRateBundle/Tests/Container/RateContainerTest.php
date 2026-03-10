<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RateContainerTest extends TestCase
{
    use CurrencyTrait;

    protected function setUp(): void
    {
    }

    public function testPropertiesArePublicAndReadable(): void
    {
        $base = self::getRub();
        $target = self::getUsd();
        $date = new DateTimeImmutable('2026-03-06');
        $value = BigDecimal::of('75.50');
        $c = new RateContainer($base, $target, $value, $date);
        $this->assertSame($base, $c->baseCurrency);
        $this->assertSame($target, $c->targetCurrency);
        $this->assertSame($c->rate->toString(), $value->toString());
        $this->assertSame($date, $c->date);
    }

    public function testSameCurrencyPairIsAllowed(): void
    {
        $rub = self::getRub();
        $c = new RateContainer(self::getRub(), self::getRub(), BigDecimal::of('1'), new DateTimeImmutable());
        $this->assertSame(self::getRub(), $c->baseCurrency);
        $this->assertSame(self::getRub(), $c->targetCurrency);
    }

    public function testEmptyRateStringIsAllowed(): void
    {
        $c = new RateContainer(
            self::getRub(),
            self::getUsd(),
            BigDecimal::of('0'),
            new DateTimeImmutable()
        );
        $this->assertSame($c->rate->toString(), BigDecimal::of('0')->toString());
    }

    public function testNegativeRateIsAllowed(): void
    {
        $value = BigDecimal::of('-0.5');
        $c = new RateContainer(
            self::getRub(),
            self::getUsd(),
            $value,
            new DateTimeImmutable()
        );
        $this->assertSame($c->rate->toString(), $value->toString());
    }
}
