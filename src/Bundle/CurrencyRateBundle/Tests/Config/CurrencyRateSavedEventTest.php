<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Config;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

class CurrencyRateSavedEventTest extends TestCase
{
    public function testExtendsSymfonyEvent(): void
    {
        $event = $this->createEvent();
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testGetRateReturnsInjectedContainer(): void
    {
        $rate = new RateContainer(
            new Currency('RUB'),
            new Currency('USD'),
            BigDecimal::of('75.50'),
            new DateTimeImmutable('2026-03-06')
        );
        $event = new CurrencyRateSavedEvent($rate);
        $this->assertSame($rate, $event->getRate());
    }

    public function testRatePropertiesAreAccessible(): void
    {
        $event = $this->createEvent();
        $rate = $event->getRate();
        $this->assertSame('RUB', $rate->baseCurrency->getCode());
        $this->assertSame('EUR', $rate->targetCurrency->getCode());
        $this->assertSame(BigDecimal::of('90.5')->toString(), $rate->rate->toString());
    }

    private function createEvent(): CurrencyRateSavedEvent
    {
        return new CurrencyRateSavedEvent(
            new RateContainer(
                new Currency('RUB'),
                new Currency('EUR'),
                BigDecimal::of('90.5'),
                new DateTimeImmutable()
            )
        );
    }
}
