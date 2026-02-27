<?php

declare(strict_types=1);

namespace App\Tests\CurrencyRateProvider\Domain\Service;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use App\Domain\Service\CurrencyRateProvider\RateFetcher;
use App\Tests\KernelTestCase;
use App\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;

class RateFetcherTest extends KernelTestCase
{
    use CurrencyTrait;

    private CurrencyRateProviderInterface $rateProvider;
    private RateFetcher $rateFetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initCurrencies();

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
            $this->rubCurrency,
            $this->usdCurrency,
            '75.50',
            $date
        );

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willReturnCallback(function () use ($expectedRate) {
                yield [$expectedRate];
            });

        $result = iterator_to_array($this->rateFetcher->fetchRates($date));

        $this->assertCount(1, $result);
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
            $this->rubCurrency,
            $this->eurCurrency,
            '85.00',
            $date
        );

        $this->rateProvider
            ->expects($this->once())
            ->method('getRates')
            ->with($date)
            ->willReturnCallback(function () use ($expectedRate) {
                yield [$expectedRate];
            });

        $result = $this->rateFetcher->tryFetchRates($date);

        $this->assertInstanceOf(\Generator::class, $result);
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
            ->willReturnCallback(function () {
                return;
                yield;
            });

        $result = $fetcher->fetchRates($date);

        $this->assertInstanceOf(\Generator::class, $result);
        $this->assertEmpty(iterator_to_array($result));
    }
}

