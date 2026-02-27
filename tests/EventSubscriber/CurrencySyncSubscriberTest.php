<?php

namespace App\Tests\EventSubscriber;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\CurrencyEnum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Entity\CurrentRate;
use App\Event\RateSavedEvent;
use App\EventSubscriber\CurrencySyncSubscriber;
use App\Repository\CurrentRateRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CurrencySyncSubscriberTest extends TestCase
{
    private CurrentRateRepository $repository;
    private CurrencySyncSubscriber $subscriber;

    protected function setUp(): void
    {
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
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
            75.50,
            $today
        );

        $currentRate = new CurrentRate(CurrencyEnum::RUB, CurrencyEnum::USD, '74.00');

        $this->repository
            ->expects($this->once())
            ->method('updateOrCreate')
            ->with('USD', '75.5', 1)
            ->willReturn($currentRate);

        $this->repository
            ->expects($this->once())
            ->method('findByCurrency')
            ->with('USD')
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
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
            75.50,
            $yesterday
        );

        $this->repository
            ->expects($this->never())
            ->method('updateOrCreate');

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
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
            75.50,
            $today
        );

        $this->repository
            ->expects($this->once())
            ->method('updateOrCreate')
            ->willReturn(new CurrentRate(CurrencyEnum::RUB, CurrencyEnum::USD, '75.50'));

        $this->repository
            ->expects($this->once())
            ->method('findByCurrency')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('save');

        $event = new RateSavedEvent($rate);
        $this->subscriber->onRateSaved($event);
    }
}

