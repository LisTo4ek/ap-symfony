<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateHistoryCbrProcessorService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use DateTimeImmutable;
use Generator;
use Money\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class CurrencyRateHistoryCbrProcessorServiceTest extends TestCase
{
    private CurrencyRateProviderServiceInterface&MockObject $provider;
    private RateHistoryStorageInterface&MockObject $storage;
    private EventDispatcherInterface&MockObject $dispatcher;
    private CurrencyRateHistoryCbrProcessorService $service;
    protected function setUp(): void
    {
        $this->provider = $this->createMock(CurrencyRateProviderServiceInterface::class);
        $this->storage = $this->createMock(RateHistoryStorageInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new CurrencyRateHistoryCbrProcessorService(
            $this->provider,
            $this->storage,
            $this->dispatcher,
        );
    }
    public function testProcessReturnsZeroForEmptyRates(): void
    {
        $this->provider->method('getRates')->willReturn($this->emptyGenerator());
        $count = $this->service->process(new DateTimeImmutable('2026-03-06'));
        $this->assertSame(0, $count);
    }
    public function testProcessSavesRatesToStorage(): void
    {
        $date = new DateTimeImmutable('yesterday');
        $rates = [
            new RateContainer(new Currency('RUB'), new Currency('USD'), '75', $date),
            new RateContainer(new Currency('RUB'), new Currency('EUR'), '85', $date),
        ];
        $this->provider->method('getRates')->willReturn($this->generatorFromChunks([$rates]));
        $this->storage
            ->expects($this->once())
            ->method('saveBatch')
            ->with($this->callback(fn(array $entities) => count($entities) === 2));
        $this->service->process($date);
    }
    public function testProcessReturnsTotalCount(): void
    {
        $date = new DateTimeImmutable('yesterday');
        $chunk1 = [
            new RateContainer(new Currency('RUB'), new Currency('USD'), '75', $date),
        ];
        $chunk2 = [
            new RateContainer(new Currency('RUB'), new Currency('EUR'), '85', $date),
            new RateContainer(new Currency('RUB'), new Currency('GBP'), '92', $date),
        ];
        $this->provider->method('getRates')->willReturn($this->generatorFromChunks([$chunk1, $chunk2]));
        $count = $this->service->process($date);
        $this->assertSame(3, $count);
    }
    public function testProcessDispatchesEventForTodaysRates(): void
    {
        $today = new DateTimeImmutable('today');
        $rates = [
            new RateContainer(new Currency('RUB'), new Currency('USD'), '75', $today),
        ];
        $this->provider->method('getRates')->willReturn($this->generatorFromChunks([$rates]));
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CurrencyRateSavedEvent::class));
        $this->service->process($today);
    }
    public function testProcessDoesNotDispatchEventForOldRates(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $rates = [
            new RateContainer(new Currency('RUB'), new Currency('USD'), '75', $yesterday),
        ];
        $this->provider->method('getRates')->willReturn($this->generatorFromChunks([$rates]));
        $this->dispatcher->expects($this->never())->method('dispatch');
        $this->service->process($yesterday);
    }
    public function testProcessHandlesMultipleChunks(): void
    {
        $date = new DateTimeImmutable('yesterday');
        $chunk1 = [new RateContainer(new Currency('RUB'), new Currency('USD'), '75', $date)];
        $chunk2 = [new RateContainer(new Currency('RUB'), new Currency('EUR'), '85', $date)];
        $this->provider->method('getRates')->willReturn($this->generatorFromChunks([$chunk1, $chunk2]));
        $this->storage->expects($this->exactly(2))->method('saveBatch');
        $count = $this->service->process($date);
        $this->assertSame(2, $count);
    }
    /**
     * @return Generator<int, array<RateContainer>>
     */
    private function emptyGenerator(): Generator
    {
        yield from [];
    }
    /**
     * @param array<array<RateContainer>> $chunks
     * @return Generator<int, array<RateContainer>>
     */
    private function generatorFromChunks(array $chunks): Generator
    {
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }
}
