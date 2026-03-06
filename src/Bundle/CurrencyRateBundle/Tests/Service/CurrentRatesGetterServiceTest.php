<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrentRatesGetterService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrentRatesGetterServiceTest extends TestCase
{
    private PaginationServiceInterface&MockObject $paginator;
    private CurrentRateStorageInterface&MockObject $storage;
    private CurrentRatesGetterService $service;
    private PaginationConfigDefault $config;
    protected function setUp(): void
    {
        $this->paginator = $this->createMock(PaginationServiceInterface::class);
        $this->storage = $this->createMock(CurrentRateStorageInterface::class);
        $this->service = new CurrentRatesGetterService($this->paginator, $this->storage);
        $this->config = new PaginationConfigDefault();
    }
    public function testReturnsLatestDateAndNullPaginationWhenBaseCurrencyEmpty(): void
    {
        $latestDate = new DateTimeImmutable();
        $this->storage->method('getLatestDate')->willReturn($latestDate);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: '',
        );
        [$date, $pagination] = $this->service->get($this->config, $dto);
        $this->assertSame($latestDate, $date);
        $this->assertNull($pagination);
    }
    public function testReturnsNullPaginationWhenNoLatestDate(): void
    {
        $this->storage->method('getLatestDate')->willReturn(null);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: 'RUB',
        );
        [$date, $pagination] = $this->service->get($this->config, $dto);
        $this->assertNull($date);
        $this->assertNull($pagination);
    }
    public function testReturnsPaginatedResultWhenDateExists(): void
    {
        $latestDate = new DateTimeImmutable();
        $this->storage->method('getLatestDate')->willReturn($latestDate);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $paginationResult = $this->createMock(PaginationResultInterface::class);
        $this->paginator->method('paginate')->willReturn($paginationResult);
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(2, 25),
            baseCurrencyCode: 'RUB',
        );
        [$date, $pagination] = $this->service->get($this->config, $dto);
        $this->assertSame($latestDate, $date);
        $this->assertSame($paginationResult, $pagination);
    }
    public function testPaginatorReceivesCorrectArguments(): void
    {
        $latestDate = new DateTimeImmutable();
        $this->storage->method('getLatestDate')->willReturn($latestDate);
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByDateAndBaseCurrency')->willReturn($pageable);
        $this->paginator
            ->expects($this->once())
            ->method('paginate')
            ->with($pageable, $this->config, 25, 3)
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(3, 25),
            baseCurrencyCode: 'USD',
        );
        $this->service->get($this->config, $dto);
    }
    public function testStorageCalledWithCorrectCurrency(): void
    {
        $latestDate = new DateTimeImmutable();
        $this->storage->method('getLatestDate')->willReturn($latestDate);
        $this->storage
            ->expects($this->once())
            ->method('findByDateAndBaseCurrency')
            ->with(
                $latestDate,
                $this->callback(fn($c) => $c->getCode() === 'EUR')
            )
            ->willReturn($this->createMock(PaginationPageableServiceInterface::class));
        $this->paginator->method('paginate')
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: 'EUR',
        );
        $this->service->get($this->config, $dto);
    }
}
