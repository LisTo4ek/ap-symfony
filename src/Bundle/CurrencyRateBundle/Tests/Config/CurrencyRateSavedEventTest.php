<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Config;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

class CurrencyRateSavedEventTest extends TestCase
{
    use CurrencyTrait;

    public function testExtendsSymfonyEvent(): void
    {
        $event = $this->createEvent();
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testGetRateReturnsInjectedContainer(): void
    {
        $rate = new RateContainer(
            self::getRub(),
            self::getUsd(),
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
        $this->assertSame(self::getRub(), $rate->baseCurrency);
        $this->assertSame(self::getEur(), $rate->targetCurrency);
        $this->assertSame(BigDecimal::of('90.5')->toString(), $rate->rate->toString());
    }

    private function createEvent(): CurrencyRateSavedEvent
    {
        return new CurrencyRateSavedEvent(
            new RateContainer(
                self::getRub(),
                self::getEur(),
                BigDecimal::of('90.5'),
                new DateTimeImmutable()
            )
        );
    }
}
