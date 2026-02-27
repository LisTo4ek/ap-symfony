<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Entity\CurrentRate;
use App\Event\RateSavedEvent;
use App\EventSubscriber\CurrencySyncSubscriber;
use App\Repository\CurrentRateRepository;
use App\Tests\KernelTestCase;
use App\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;

class CurrencySyncSubscriberTest extends KernelTestCase
{
    use CurrencyTrait;

    private CurrentRateRepository $repository;
    private CurrencySyncSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initCurrencies();

        $this->repository = $this->createMock(CurrentRateRepository::class);
        $this->subscriber = new CurrencySyncSubscriber($this->repository);
    }

    /**
     * Test case 1: Updates current rate when rate is for today (requirement 3.1)
     */
    public function testUpdatesCurrentRateForTodayRate(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new Rate(
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

        $this->repository
            ->expects($this->once())
            ->method('findByCurrencyPair')
            ->with($this->rubCurrency, $this->usdCurrency)
            ->willReturn($currentRate);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($currentRate, true);

        $event = new RateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }

    /**
     * Test case 2: Does not update current rate when rate is not for today
     */
    public function testDoesNotUpdateCurrentRateForOldRate(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $rate = new Rate(
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

        $event = new RateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }

    /**
     * Test case 3: Subscriber is properly registered
     */
    public function testSubscriberIsRegistered(): void
    {
        $subscribedEvents = CurrencySyncSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(RateSavedEvent::class, $subscribedEvents);
        $this->assertEquals('onRateSaved', $subscribedEvents[RateSavedEvent::class]);
    }

    /**
     * Test case 4: Handles missing current rate gracefully
     */
    public function testHandlesMissingCurrentRate(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new Rate(
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
            ->expects($this->once())
            ->method('findByCurrencyPair')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('save');

        $event = new RateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }
}

