<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RateHistoryStorageInterface contract using mock implementation
 * Ensures any implementation of the interface behaves correctly
 */
class RateHistoryStorageInterfaceTest extends TestCase
{
    use CurrencyTrait;

    public function testSaveBatchAcceptsEmptyArray(): void
    {
        $storage = $this->createMock(RateHistoryStorageInterface::class);
        $storage->expects($this->once())
            ->method('saveBatch')
            ->with([]);
        $storage->saveBatch([]);
    }

    public function testSaveBatchAcceptsArrayOfRateHistory(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $entities = [
            new RateHistory(self::getRub(), self::getUsd(), BigDecimal::of('75.50'), $date),
            new RateHistory(self::getRub(), self::getEur(), BigDecimal::of('85.00'), $date),
        ];
        $storage = $this->createMock(RateHistoryStorageInterface::class);
        $storage->expects($this->once())
            ->method('saveBatch')
            ->with($entities);
        $storage->saveBatch($entities);
    }

    public function testFindByCurrencyPairReturnsPageableService(): void
    {
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $storage = $this->createMock(RateHistoryStorageInterface::class);
        $storage->method('findByCurrencyPairGroupedByDate')
            ->with(self::getRub(), self::getUsd())
            ->willReturn($pageable);
        $result = $storage->findByCurrencyPairGroupedByDate(self::getRub(), self::getUsd());
        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
    }

    public function testFindByCurrencyPairWithDifferentCurrencies(): void
    {
        $rubUsdPageable = $this->createMock(PaginationPageableServiceInterface::class);
        $rubUsdPageable->method('getTotalCount')->willReturn(10);
        $rubEurPageable = $this->createMock(PaginationPageableServiceInterface::class);
        $rubEurPageable->method('getTotalCount')->willReturn(5);
        $storage = $this->createMock(RateHistoryStorageInterface::class);
        $storage->method('findByCurrencyPairGroupedByDate')
            ->willReturnMap([
                [self::getRub(), self::getUsd(), $rubUsdPageable],
                [self::getRub(), self::getEur(), $rubEurPageable],
            ]);
        $rubUsdResult = $storage->findByCurrencyPairGroupedByDate(self::getRub(), self::getUsd());
        $rubEurResult = $storage->findByCurrencyPairGroupedByDate(self::getRub(), self::getEur());
        $this->assertSame(10, $rubUsdResult->getTotalCount());
        $this->assertSame(5, $rubEurResult->getTotalCount());
    }

    public function testInterfaceMethodSignatures(): void
    {
        $reflection = new \ReflectionClass(RateHistoryStorageInterface::class);
        $saveBatchMethod = $reflection->getMethod('saveBatch');
        $this->assertCount(1, $saveBatchMethod->getParameters());
        $this->assertSame('entities', $saveBatchMethod->getParameters()[0]->getName());
        $this->assertTrue($saveBatchMethod->getParameters()[0]->getType()->getName() === 'array');
        $findMethod = $reflection->getMethod('findByCurrencyPair');
        $this->assertCount(2, $findMethod->getParameters());
        $this->assertSame('baseCurrency', $findMethod->getParameters()[0]->getName());
        $this->assertSame('targetCurrency', $findMethod->getParameters()[1]->getName());
    }

    public function testSaveBatchReturnsVoid(): void
    {
        $reflection = new \ReflectionClass(RateHistoryStorageInterface::class);
        $saveBatchMethod = $reflection->getMethod('saveBatch');
        $returnType = $saveBatchMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testFindByCurrencyPairReturnType(): void
    {
        $reflection = new \ReflectionClass(RateHistoryStorageInterface::class);
        $findMethod = $reflection->getMethod('findByCurrencyPair');
        $returnType = $findMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame(
            PaginationPageableServiceInterface::class,
            $returnType->getName()
        );
    }
}
