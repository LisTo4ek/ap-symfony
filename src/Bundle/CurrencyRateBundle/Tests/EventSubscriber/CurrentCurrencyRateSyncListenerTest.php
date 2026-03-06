<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\EventSubscriber;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\EventListener\CurrentCurrencyRateSyncListener;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
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
        $this->initCurrencies();
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
        $rate = new RateContainer($this->rubCurrency, $this->usdCurrency, '75.50', $today);
        $this->storage
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->with($this->rubCurrency, $this->usdCurrency, '75.50', $today)
            ->willReturn(new CurrentRate($this->rubCurrency, $this->usdCurrency, '75.50', $today));
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }
    public function testDoesNotUpsertWhenRateIsNotForToday(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $rate = new RateContainer($this->rubCurrency, $this->usdCurrency, '75.50', $yesterday);
        $this->storage->expects($this->never())->method('upsertForCurrencyPair');
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }
    public function testDoesNotUpsertForFutureDates(): void
    {
        $tomorrow = new DateTimeImmutable('tomorrow');
        $rate = new RateContainer($this->rubCurrency, $this->eurCurrency, '85', $tomorrow);
        $this->storage->expects($this->never())->method('upsertForCurrencyPair');
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }
    public function testPassesExactCurrencyAndValueToStorage(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new RateContainer($this->eurCurrency, $this->rubCurrency, '0.0105', $today);
        $this->storage
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->with(
                $this->callback(fn($c) => $c->getCode() === 'EUR'),
                $this->callback(fn($c) => $c->getCode() === 'RUB'),
                '0.0105',
                $today,
            )
            ->willReturn(new CurrentRate($this->eurCurrency, $this->rubCurrency, '0.0105', $today));
        $this->listener->onRateSaved(new CurrencyRateSavedEvent($rate));
    }
}
