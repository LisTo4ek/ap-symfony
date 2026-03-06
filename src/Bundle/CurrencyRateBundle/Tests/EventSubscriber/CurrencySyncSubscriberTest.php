<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\EventSubscriber;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\EventListener\CurrentCurrencyRateSyncListener;
use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use App\Bundle\CurrencyRateBundle\Tests\KernelTestCase;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;

class CurrencySyncSubscriberTest extends KernelTestCase
{
    use CurrencyTrait;

    private CurrentRateRepository $repository;
    private CurrentCurrencyRateSyncListener $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initCurrencies();

        $this->repository = $this->createMock(CurrentRateRepository::class);
        $this->subscriber = new CurrentCurrencyRateSyncListener($this->repository);
    }

    /**
     * Test case 1: Updates current rate when rate is for today (requirement 3.1)
     */
    public function testUpdatesCurrentRateForTodayRate(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new RateContainer(
            $this->rubCurrency,
            $this->usdCurrency,
            '75.50',
            $today
        );

        $currentRate = new CurrentRate($this->rubCurrency, $this->usdCurrency, '74.00', $today);

        $this->repository
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->with($this->rubCurrency, $this->usdCurrency, '75.50', $today)
            ->willReturn($currentRate);

//        $this->repository
//            ->expects($this->once())
//            ->method('findByCurrencyPair')
//            ->with($this->rubCurrency, $this->usdCurrency)
//            ->willReturn($currentRate);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($currentRate, true);

        $event = new CurrencyRateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }

    /**
     * Test case 2: Does not update current rate when rate is not for today
     */
    public function testDoesNotUpdateCurrentRateForOldRate(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $rate = new RateContainer(
            $this->rubCurrency,
            $this->usdCurrency,
            '75.50',
            $yesterday
        );

        $this->repository
            ->expects($this->never())
            ->method('upsertForCurrencyPair');

        $this->repository
            ->expects($this->never())
            ->method('save');

        $event = new CurrencyRateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }

    /**
     * Test case 3: Subscriber is properly registered
     */
    public function testSubscriberIsRegistered(): void
    {
        $subscribedEvents = CurrentCurrencyRateSyncListener::getSubscribedEvents();

        $this->assertArrayHasKey(CurrencyRateSavedEvent::class, $subscribedEvents);
        $this->assertEquals('onRateSaved', $subscribedEvents[CurrencyRateSavedEvent::class]);
    }

    /**
     * Test case 4: Handles missing current rate gracefully
     */
    public function testHandlesMissingCurrentRate(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new RateContainer(
            $this->rubCurrency,
            $this->usdCurrency,
            '75.50',
            $today
        );

        $this->repository
            ->expects($this->once())
            ->method('upsertForCurrencyPair')
            ->willReturn(new CurrentRate($this->rubCurrency, $this->usdCurrency, '75.50', $today));

        $this->repository
            ->expects($this->never())
            ->method('save');

        $event = new CurrencyRateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }
}
