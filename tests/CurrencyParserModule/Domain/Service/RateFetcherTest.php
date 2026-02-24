<?php

namespace App\Tests\RateProvider\Domain\Service;

use App\RateProvider\Domain\Contract\RateProviderInterface;
use App\RateProvider\Domain\Entity\Rate;
use App\RateProvider\Domain\Service\RateFetcher;
use App\RateProvider\Domain\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

class RateFetcherTest extends TestCase
{
    private RateProviderInterface $rateProvider;
    private RateFetcher $rateFetcher;

    protected function setUp(): void
    {
        $this->rateProvider = $this->createMock(RateProviderInterface::class);
        $this->rateFetcher = new RateFetcher($this->rateProvider);
    }

    /**
     * Test case 1: Successfully fetches rates from provider
     */
    public function testFetchRatesSuccessfully(): void
    {
        $date = new \DateTimeImmutable('2026-01-01');
        $expectedRate = new Rate(
            Currency::RUB,
            Currency::USD,
            75.50,
            $date
        );

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willReturn([$expectedRate]);

        $result = $this->rateFetcher->fetchRates($date);

        $this->assertCount(1, $result);
        $this->assertSame($expectedRate, $result[0]);
    }

    /**
     * Test case 2: Propagates exception when provider fails
     */
    public function testFetchRatesThrowsException(): void
    {
        $date = new \DateTimeImmutable('2026-01-01');

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willThrowException(new \RuntimeException('CBR service unavailable'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CBR service unavailable');

        $this->rateFetcher->fetchRates($date);
    }

    /**
     * Test case 3: tryFetchRates returns null on error
     */
    public function testTryFetchRatesReturnsNullOnError(): void
    {
        $date = new \DateTimeImmutable('2026-01-01');

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willThrowException(new \RuntimeException('Connection failed'));

        $result = $this->rateFetcher->tryFetchRates($date);

        $this->assertNull($result);
    }

    /**
     * Test case 4: tryFetchRates returns rates on success
     */
    public function testTryFetchRatesReturnsRatesOnSuccess(): void
    {
        $date = new \DateTimeImmutable('2026-01-01');
        $expectedRate = new Rate(
            Currency::RUB,
            Currency::EUR,
            85.00,
            $date
        );

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willReturn([$expectedRate]);

        $result = $this->rateFetcher->tryFetchRates($date);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($expectedRate, $result[0]);
    }

    /**
     * Test case 5: Works with any RateProviderInterface implementation
     */
    public function testWorksWithAnyRateProvider(): void
    {
        // This test demonstrates that RateFetcher depends on interface, not concrete class
        $customProvider = $this->createMock(RateProviderInterface::class);
        $fetcher = new RateFetcher($customProvider);

        $date = new \DateTimeImmutable('2026-01-01');
        $customProvider
            ->method('getRates')
            ->willReturn([]);

        $result = $fetcher->fetchRates($date);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

