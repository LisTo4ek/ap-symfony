<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Storage;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CurrentRateStorageInterface contract using mock implementation
 * Ensures any implementation of the interface behaves correctly
 */
class CurrentRateStorageInterfaceTest extends TestCase
{
    use CurrencyTrait;

    public function testUpsertForCurrencyPairReturnsCurrentRateEntity(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $value = BigDecimal::of('75.50');
        $expectedRate = new CurrentRate(self::getRub(), self::getUsd(), $value, $date);
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('upsertForCurrencyPair')
            ->with(self::getRub(), self::getUsd(), $value, $date)
            ->willReturn($expectedRate);
        $result = $storage->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            $value,
            $date
        );
        $this->assertInstanceOf(CurrentRate::class, $result);
        $this->assertSame(self::getRub(), $result->getBaseCurrency());
        $this->assertSame(self::getUsd(), $result->getTargetCurrency());
        $this->assertSame('75.50', $result->getValue()->toString());
    }

    public function testHasRecordsByDayReturnsBool(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('hasRecordsByDay')
            ->with($date)
            ->willReturn(true);
        $this->assertTrue($storage->hasRecordsByDay($date));
    }

    public function testHasRecordsByDayCanReturnFalse(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('hasRecordsByDay')
            ->with($date)
            ->willReturn(false);
        $this->assertFalse($storage->hasRecordsByDay($date));
    }

    public function testGetLatestDateReturnsNullWhenEmpty(): void
    {
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('getLatestDate')
            ->willReturn(null);
        $this->assertNull($storage->getLatestDate());
    }

    public function testGetLatestDateReturnsDateTimeImmutable(): void
    {
        $expectedDate = new DateTimeImmutable('2026-03-06');
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('getLatestDate')
            ->willReturn($expectedDate);
        $result = $storage->getLatestDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-03-06', $result->format('Y-m-d'));
    }

    public function testFindByDateAndBaseCurrencyReturnsPageableService(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $storage = $this->createMock(CurrentRateStorageInterface::class);
        $storage->method('findByDateAndBaseCurrency')
            ->with($date, self::getRub())
            ->willReturn($pageable);
        $result = $storage->findByDateAndBaseCurrency($date, self::getRub());
        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
    }

    public function testInterfaceMethodSignatures(): void
    {
        $reflection = new \ReflectionClass(CurrentRateStorageInterface::class);
        $upsertMethod = $reflection->getMethod('upsertForCurrencyPair');
        $this->assertCount(4, $upsertMethod->getParameters());
        $this->assertSame('baseCurrency', $upsertMethod->getParameters()[0]->getName());
        $this->assertSame('targetCurrency', $upsertMethod->getParameters()[1]->getName());
        $this->assertSame('value', $upsertMethod->getParameters()[2]->getName());
        $this->assertSame('date', $upsertMethod->getParameters()[3]->getName());
        $hasRecordsMethod = $reflection->getMethod('hasRecordsByDay');
        $this->assertCount(1, $hasRecordsMethod->getParameters());
        $this->assertSame('date', $hasRecordsMethod->getParameters()[0]->getName());
        $getLatestDateMethod = $reflection->getMethod('getLatestDate');
        $this->assertCount(0, $getLatestDateMethod->getParameters());
        $findMethod = $reflection->getMethod('findByDateAndBaseCurrency');
        $this->assertCount(2, $findMethod->getParameters());
        $this->assertSame('date', $findMethod->getParameters()[0]->getName());
        $this->assertSame('baseCurrency', $findMethod->getParameters()[1]->getName());
    }
}
