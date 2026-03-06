<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
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
        $c = new RateContainer($base, $target, '75.50', $date);
        $this->assertSame($base, $c->baseCurrency);
        $this->assertSame($target, $c->targetCurrency);
        $this->assertSame('75.50', $c->rate);
        $this->assertSame($date, $c->date);
    }
    public function testSameCurrencyPairIsAllowed(): void
    {
        $rub = new Currency('RUB');
        $c = new RateContainer($rub, $rub, '1', new DateTimeImmutable());
        $this->assertSame('RUB', $c->baseCurrency->getCode());
        $this->assertSame('RUB', $c->targetCurrency->getCode());
    }
    public function testEmptyRateStringIsAllowed(): void
    {
        $c = new RateContainer(
            new Currency('RUB'),
            new Currency('USD'),
            '',
            new DateTimeImmutable()
        );
        $this->assertSame('', $c->rate);
    }
    public function testNegativeRateIsAllowed(): void
    {
        $c = new RateContainer(
            new Currency('RUB'),
            new Currency('USD'),
            '-0.5',
            new DateTimeImmutable()
        );
        $this->assertSame('-0.5', $c->rate);
    }
}
