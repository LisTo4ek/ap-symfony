<?php

namespace App\Tests\CurrencyRateProvider\Domain\Service;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\CurrencyEnum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Entity\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use App\Domain\Service\CurrencyRateProvider\RateFetcher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RateFetcherTest extends TestCase
{
    private CurrencyRateProviderInterface $rateProvider;
    private RateFetcher $rateFetcher;

    protected function setUp(): void
    {
        $this->rateProvider = $this->createMock(CurrencyRateProviderInterface::class);
        $this->rateFetcher = new RateFetcher($this->rateProvider);
    }

    /**
     * Test case 1: Successfully fetches rates from provider
     */
    public function testFetchRatesSuccessfully(): void
    {
        $date = new DateTimeImmutable('2026-01-01');
        $expectedRate = new Rate(
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
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
        $date = new DateTimeImmutable('2026-01-01');

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
        $date = new DateTimeImmutable('2026-01-01');

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
        $date = new DateTimeImmutable('2026-01-01');
        $expectedRate = new Rate(
            CurrencyEnum::RUB,
            CurrencyEnum::EUR,
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
        $customProvider = $this->createMock(CurrencyRateProviderInterface::class);
        $fetcher = new RateFetcher($customProvider);

        $date = new DateTimeImmutable('2026-01-01');
        $customProvider
            ->method('getRates')
            ->willReturn([]);

        $result = $fetcher->fetchRates($date);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

