<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProcessorException;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateHistoryCbrProcessorService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrentRatesGetterService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CurrentRatesGetterServiceTest extends TestCase
{
    use CurrencyTrait;

    private PaginationServiceInterface&MockObject $paginator;
    private CurrentRateStorageInterface&MockObject $storage;
    private CurrencyRateHistoryCbrProcessorService&MockObject $processor;
    private CurrentRatesGetterService $service;
    private PaginationConfigDefault $config;

    protected function setUp(): void
    {
        $this->paginator = $this->createMock(PaginationServiceInterface::class);
        $this->storage = $this->createMock(CurrentRateStorageInterface::class);
        $this->processor = $this->createMock(CurrencyRateHistoryCbrProcessorService::class);
        $this->service = new CurrentRatesGetterService(
            $this->paginator,
            $this->storage,
            $this->processor,
        );
        $this->config = new PaginationConfigDefault();
    }

    public function testReturnsLatestDateAndNullPaginationWhenBaseCurrencyEmpty(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->method('getLatestDate')->willReturn($date);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: '',
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertSame($date, $result->latestDate);
        $this->assertNull($result->pagination);
        $this->assertNull($result->importRatesException);
    }

    public function testReturnsNullPaginationWhenNoLatestDateAndProcessorFails(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $exception = new ProcessorException('import failed');
        $this->storage->method('getLatestDate')->willReturn(null);
        $this->processor->method('process')->willThrowException($exception);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertNull($result->latestDate);
        $this->assertNull($result->pagination);
        $this->assertSame($exception, $result->importRatesException);
    }

    public function testTriggersImportWhenNoLatestDate(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->expects($this->exactly(2))
            ->method('getLatestDate')
            ->willReturnOnConsecutiveCalls(null, $date);
        $this->processor->expects($this->once())
            ->method('process')
            ->with($date);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $paginationResult = $this->createMock(PaginationResultInterface::class);
        $this->paginator->method('paginate')->willReturn($paginationResult);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertSame($date, $result->latestDate);
        $this->assertSame($paginationResult, $result->pagination);
        $this->assertNull($result->importRatesException);
    }

    public function testTriggersImportWhenLatestDateDiffersFromRequestedDate(): void
    {
        $requestedDate = new DateTimeImmutable('2026-03-10');
        $oldDate = new DateTimeImmutable('2026-03-09');
        $this->storage->expects($this->exactly(2))
            ->method('getLatestDate')
            ->willReturnOnConsecutiveCalls($oldDate, $requestedDate);
        $this->processor->expects($this->once())
            ->method('process')
            ->with($requestedDate);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $paginationResult = $this->createMock(PaginationResultInterface::class);
        $this->paginator->method('paginate')->willReturn($paginationResult);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $requestedDate);
        $this->assertSame($requestedDate, $result->latestDate);
        $this->assertNull($result->importRatesException);
    }

    public function testSkipsImportWhenLatestDateMatchesRequestedDate(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->method('getLatestDate')->willReturn($date);
        $this->processor->expects($this->never())->method('process');
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $this->paginator->method('paginate')
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertNull($result->importRatesException);
    }

    public function testReturnsPaginatedResultWhenDateExists(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->method('getLatestDate')->willReturn($date);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $paginationResult = $this->createMock(PaginationResultInterface::class);
        $this->paginator->method('paginate')->willReturn($paginationResult);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(2, 25),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertSame($date, $result->latestDate);
        $this->assertSame($paginationResult, $result->pagination);
    }

    public function testPaginatorReceivesCorrectArguments(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->method('getLatestDate')->willReturn($date);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $this->paginator
            ->expects($this->once())
            ->method('paginate')
            ->with($pageable, $this->config, 25, 3)
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(3, 25),
            baseCurrencyCode: self::getUsd()->getCode(),
        );
        $this->service->get($this->config, $dto, $date);
    }

    public function testStorageCalledWithCorrectCurrency(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $this->storage->method('getLatestDate')->willReturn($date);
        $this->storage
            ->expects($this->once())
            ->method('findByDateAndBaseCurrency')
            ->with(
                $date,
                $this->callback(fn($c) => $c->getCode() === self::getEur()->getCode())
            )
            ->willReturn($this->createMock(PaginationPageableServiceInterface::class));
        $this->paginator->method('paginate')
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getEur()->getCode(),
        );
        $this->service->get($this->config, $dto, $date);
    }

    public function testImportExceptionIsCapturedAndReturned(): void
    {
        $date = new DateTimeImmutable('2026-03-10');
        $exception = new ProcessorException('CBR unavailable');
        $this->storage->method('getLatestDate')->willReturn(null);
        $this->processor->method('process')->willThrowException($exception);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
        );
        $result = $this->service->get($this->config, $dto, $date);
        $this->assertSame($exception, $result->importRatesException);
        $this->assertNull($result->latestDate);
        $this->assertNull($result->pagination);
    }
}
