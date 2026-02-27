<?php

namespace App\Tests\CurrencyRateProvider\Domain\Action;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\CurrencyEnum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Domain\Action\CurrencyRateProvider\GetCurrentRatesAction;
use App\Domain\Service\CurrencyRateProvider\RateFetcher;
use App\Domain\Service\CurrencyRateProvider\RateManager;
use App\Entity\CurrentRate;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GetCurrentRatesActionTest extends TestCase
{
    private RateManager $rateManager;
    private RateFetcher $rateFetcher;
    private GetCurrentRatesAction $action;

    protected function setUp(): void
    {
        $this->rateManager = $this->createMock(RateManager::class);
        $this->rateFetcher = $this->createMock(RateFetcher::class);
        $this->action = new GetCurrentRatesAction($this->rateManager, $this->rateFetcher);
    }

    /**
     * Test case 1: Current rates exist and are actual (from today)
     */
    public function testReturnsCurrentRatesWhenActual(): void
    {
        $today = new DateTimeImmutable('today');
        $currentRate = new CurrentRate(CurrencyEnum::USD, '75.50');

        $this->rateManager
            ->expects($this->once())
            ->method('getCurrentRates')
            ->willReturn([$currentRate]);

        $this->rateFetcher
            ->expects($this->never())
            ->method('tryFetchRates');

        $result = ($this->action)();

        $this->assertTrue($result->isActual);
        $this->assertCount(1, $result->rates);
        $this->assertNull($result->message);
    }

    /**
     * Test case 2: No current rates, successfully fetch from CBR
     */
    public function testFetchesRatesWhenNoCurrentRates(): void
    {
        $today = new DateTimeImmutable('today');
        $rate = new Rate(
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
            75.50,
            $today
        );

        $this->rateManager
            ->expects($this->once())
            ->method('getCurrentRates')
            ->willReturn([]);

        $this->rateFetcher
            ->expects($this->once())
            ->method('tryFetchRates')
            ->with($this->equalTo($today))
            ->willReturn([$rate]);

        $this->rateManager
            ->expects($this->once())
            ->method('saveRatesToHistory')
            ->with([$rate]);

        $currentRate = new CurrentRate(CurrencyEnum::USD, '75.50');
        $this->rateManager
            ->expects($this->exactly(2))
            ->method('getCurrentRates')
            ->willReturnOnConsecutiveCalls([], [$currentRate]);

        $result = ($this->action)();

        $this->assertTrue($result->isActual);
        $this->assertNull($result->message);
    }

    /**
     * Test case 3 (Requirement 1.1): Connection error with existing rates
     */
    public function testReturnsOldRatesWithWarningOnConnectionError(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $currentRate = new CurrentRate(CurrencyEnum::USD, '75.50');
        // Set updated time to yesterday using reflection
        $reflection = new \ReflectionClass($currentRate);
        $property = $reflection->getProperty('updatedAt');
        $property->setAccessible(true);
        $property->setValue($currentRate, $yesterday);

        $this->rateManager
            ->expects($this->once())
            ->method('getCurrentRates')
            ->willReturn([$currentRate]);

        $this->rateFetcher
            ->expects($this->once())
            ->method('tryFetchRates')
            ->willReturn(null);

        $result = ($this->action)();

        $this->assertFalse($result->isActual);
        $this->assertCount(1, $result->rates);
        $this->assertStringContainsString('не актуальные', $result->message);
    }

    /**
     * Test case 4 (Requirement 1.2): Connection error without existing rates
     */
    public function testReturnsEmptyWithErrorWhenNoRatesAndConnectionError(): void
    {
        $this->rateManager
            ->expects($this->once())
            ->method('getCurrentRates')
            ->willReturn([]);

        $this->rateFetcher
            ->expects($this->once())
            ->method('tryFetchRates')
            ->willReturn(null);

        $result = ($this->action)();

        $this->assertFalse($result->isActual);
        $this->assertCount(0, $result->rates);
        $this->assertStringContainsString('недоступны', $result->message);
    }
}

