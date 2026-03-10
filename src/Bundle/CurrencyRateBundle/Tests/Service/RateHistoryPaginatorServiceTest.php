<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\RateHistoryPaginatorService;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateHistoryPaginatorServiceTest extends TestCase
{
    use CurrencyTrait;

    private PaginationServiceInterface&MockObject $paginator;
    private RateHistoryStorageInterface&MockObject $storage;
    private RateHistoryPaginatorService $service;
    private PaginationConfigDefault $config;

    protected function setUp(): void
    {
        $this->paginator = $this->createMock(PaginationServiceInterface::class);
        $this->storage = $this->createMock(RateHistoryStorageInterface::class);
        $this->service = new RateHistoryPaginatorService($this->paginator, $this->storage);
        $this->config = new PaginationConfigDefault();
    }

    public function testReturnsNullWhenBaseCurrencyCodeEmpty(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: '',
            targetCurrencyCode: self::getUsd()->getCode(),
        );
        $this->assertNull($this->service->get($this->config, $dto));
    }

    public function testReturnsNullWhenTargetCurrencyCodeEmpty(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
            targetCurrencyCode: '',
        );
        $this->assertNull($this->service->get($this->config, $dto));
    }

    public function testReturnsNullWhenBothCodesEmpty(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: '',
            targetCurrencyCode: '',
        );
        $this->assertNull($this->service->get($this->config, $dto));
    }

    public function testReturnsPaginationResultForValidPair(): void
    {
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByCurrencyPair')->willReturn($pageable);
        $expected = $this->createMock(PaginationResultInterface::class);
        $this->paginator->method('paginate')->willReturn($expected);
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
            targetCurrencyCode: self::getEur()->getCode(),
        );
        $result = $this->service->get($this->config, $dto);
        $this->assertSame($expected, $result);
    }

    public function testStorageCalledWithCorrectCurrencyPair(): void
    {
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage
            ->expects($this->once())
            ->method('findByCurrencyPair')
            ->with(
                $this->callback(fn($c) => $c->getCode() === self::getRub()->getCode()),
                $this->callback(fn($c) => $c->getCode() === 'GBP'),
            )
            ->willReturn($pageable);
        $this->paginator->method('paginate')
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(1, 10),
            baseCurrencyCode: self::getRub()->getCode(),
            targetCurrencyCode: 'GBP',
        );
        $this->service->get($this->config, $dto);
    }

    public function testPaginatorReceivesCorrectPageAndPerPage(): void
    {
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $this->storage->method('findByCurrencyPair')->willReturn($pageable);
        $this->paginator
            ->expects($this->once())
            ->method('paginate')
            ->with($pageable, $this->config, 50, 7)
            ->willReturn($this->createMock(PaginationResultInterface::class));
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(7, 50),
            baseCurrencyCode: self::getRub()->getCode(),
            targetCurrencyCode: self::getUsd()->getCode(),
        );
        $this->service->get($this->config, $dto);
    }
}
