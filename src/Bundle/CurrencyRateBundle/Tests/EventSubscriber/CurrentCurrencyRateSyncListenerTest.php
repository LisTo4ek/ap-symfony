<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\EventSubscriber;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\EventListener\CurrentCurrencyRateSyncListener;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrentCurrencyRateSyncListenerTest extends TestCase
{
    use CurrencyTrait;

    private CurrentRateStorageInterface&MockObject $storage;
    private CurrentCurrencyRateSyncListener $listener;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(CurrentRateStorageInterface::class);
        $this->listener = new CurrentCurrencyRateSyncListener($this->storage);
    }

    public function testSubscriberIsRegisteredForCurrencyRateSavedEvent(): void
    {
        $events = CurrentCurrencyRateSyncListener::getSubscribedEvents();
        $this->assertArrayHasKey(CurrencyRateSavedEvent::class, $events);
        $this->assertSame('onRateSaved', $events[CurrencyRateSavedEvent::class]);
    }

    public function testUpsertsCurrentRateWhenRateIsForToday(): void
    {
        $today = new DateTimeImmutable('today');
        $value = BigDecimal::of('75.50');
        $rate = new RateContainer(self::getRub(), self::getUsd(), $value, $today);
        $this->storage
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->with(self::getRub(), self::getUsd(), $value, $today)
            ->willReturn(new CurrentRate(self::getRub(), self::getUsd(), $value, $today));
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }

    public function testDoesNotUpsertWhenRateIsNotForToday(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $rate = new RateContainer(self::getRub(), self::getUsd(), BigDecimal::of('75.50'), $yesterday);
        $this->storage->expects($this->never())->method('upsertForCurrencyPair');
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }

    public function testDoesNotUpsertForFutureDates(): void
    {
        $tomorrow = new DateTimeImmutable('tomorrow');
        $rate = new RateContainer(self::getRub(), self::getEur(), BigDecimal::of('85'), $tomorrow);
        $this->storage->expects($this->never())->method('upsertForCurrencyPair');
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }

    public function testPassesExactCurrencyAndValueToStorage(): void
    {
        $today = new DateTimeImmutable('today');
        $value = BigDecimal::of('0.0105');
        $rate = new RateContainer(self::getEur(), self::getRub(), $value, $today);
        $this->storage
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->with(
                $this->callback(fn($c) => $c->getCode() === self::getEur()->getCode()),
                $this->callback(fn($c) => $c->getCode() === self::getRub()->getCode()),
                $value,
                $today,
            )
            ->willReturn(new CurrentRate(self::getEur(), self::getRub(), $value, $today));
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }
}
