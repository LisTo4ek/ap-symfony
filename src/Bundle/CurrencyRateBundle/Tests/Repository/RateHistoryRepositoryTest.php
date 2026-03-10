<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Repository\RateHistoryRepository;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\KernelTestCase;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use App\Bundle\CurrencyRateBundle\Tests\Trait\DatabaseSetupTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Money\Currency;

class RateHistoryRepositoryTest extends KernelTestCase
{
    use CurrencyTrait;
    use DatabaseSetupTrait;

    private RateHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->repository = $this->fromContainer(RateHistoryRepository::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    private function createRateHistory(
        string $baseCurrency,
        string $targetCurrency,
        string $value,
        string $date
    ): RateHistory {
        return new RateHistory(
            new Currency($baseCurrency),
            new Currency($targetCurrency),
            BigDecimal::of($value),
            new DateTimeImmutable($date)
        );
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(RateHistoryStorageInterface::class, $this->repository);
    }

    public function testSaveBatchWithEmptyArrayDoesNothing(): void
    {
        // Should not throw any exception
        $this->repository->saveBatch([]);

        // No records should exist
        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $this->assertSame(0, $result->getTotalCount());
    }

    public function testSaveBatchPersistsSingleEntity(): void
    {
        $entity = $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-06');

        $this->repository->saveBatch([$entity]);

        $this->assertNotNull($entity->getId());
        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $this->assertSame(1, $result->getTotalCount());
    }

    public function testSaveBatchPersistsMultipleEntities(): void
    {
        $entities = [
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-06'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '76.00', '2026-03-05'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '74.50', '2026-03-04'),
        ];

        $this->repository->saveBatch($entities);

        foreach ($entities as $entity) {
            $this->assertNotNull($entity->getId());
        }

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $this->assertSame(3, $result->getTotalCount());
    }

    public function testSaveBatchWithMixedCurrencyPairs(): void
    {
        $entities = [
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-06'),
            $this->createRateHistory(self::getRub()->getCode(), self::getEur()->getCode(), '85.00', '2026-03-06'),
            $this->createRateHistory(self::getUsd()->getCode(), self::getEur()->getCode(), '1.10', '2026-03-06'),
        ];

        $this->repository->saveBatch($entities);

        $rubUsdResult = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $rubEurResult = $this->repository->findByCurrencyPair(self::getRub(), self::getEur());
        $usdEurResult = $this->repository->findByCurrencyPair(self::getUsd(), self::getEur());

        $this->assertSame(1, $rubUsdResult->getTotalCount());
        $this->assertSame(1, $rubEurResult->getTotalCount());
        $this->assertSame(1, $usdEurResult->getTotalCount());
    }

    public function testFindByCurrencyPairReturnsPageableInterface(): void
    {
        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());

        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
    }

    public function testFindByCurrencyPairReturnsEmptyWhenNoMatch(): void
    {
        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());

        $this->assertSame(0, $result->getTotalCount());
        $this->assertEmpty($result->getPage(1, 10));
    }

    public function testFindByCurrencyPairFiltersCorrectly(): void
    {
        $entities = [
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-06'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '76.00', '2026-03-05'),
            $this->createRateHistory(self::getRub()->getCode(), self::getEur()->getCode(), '85.00', '2026-03-06'),
            $this->createRateHistory(self::getUsd()->getCode(), self::getEur()->getCode(), '1.10', '2026-03-06'),
        ];
        $this->repository->saveBatch($entities);

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());

        $this->assertSame(2, $result->getTotalCount());
        $items = $result->getPage(1, 10);
        foreach ($items as $item) {
            $this->assertSame(self::getRub()->getCode(), $item->getBaseCurrency()->getCode());
            $this->assertSame(self::getUsd()->getCode(), $item->getTargetCurrency()->getCode());
        }
    }

    public function testFindByCurrencyPairOrdersByDateDescending(): void
    {
        $entities = [
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '74.50', '2026-03-04'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '76.00', '2026-03-06'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-05'),
        ];
        $this->repository->saveBatch($entities);

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $items = $result->getPage(1, 10);

        // Should be ordered by date descending
        $this->assertSame('2026-03-06', $items[0]->getDate()->format('Y-m-d'));
        $this->assertSame('2026-03-05', $items[1]->getDate()->format('Y-m-d'));
        $this->assertSame('2026-03-04', $items[2]->getDate()->format('Y-m-d'));
    }

    public function testFindByCurrencyPairPaginationWorks(): void
    {
        // Create 15 entities for the same currency pair
        $entities = [];
        for ($i = 1; $i <= 15; $i++) {
            $entities[] = $this->createRateHistory(
                self::getRub()->getCode(),
                self::getUsd()->getCode(),
                (string) (70 + $i),
                sprintf('2026-03-%02d', $i)
            );
        }
        $this->repository->saveBatch($entities);

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());

        $this->assertSame(15, $result->getTotalCount());
        $this->assertSame(2, $result->getTotalPages(10));

        $page1 = $result->getPage(1, 10);
        $page2 = $result->getPage(2, 10);

        $this->assertCount(10, $page1);
        $this->assertCount(5, $page2);
    }

    public function testFindByCurrencyPairReturnsDistinctResults(): void
    {
        $entities = [
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '75.50', '2026-03-06'),
            $this->createRateHistory(self::getRub()->getCode(), self::getUsd()->getCode(), '76.00', '2026-03-05'),
        ];
        $this->repository->saveBatch($entities);

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $items = $result->getPage(1, 10);

        // Ensure no duplicates (each item should have unique ID)
        $ids = array_map(fn(RateHistory $item) => $item->getId(), $items);
        $this->assertCount(count($items), array_unique($ids));
    }

    public function testSaveBatchWithLargeBatch(): void
    {
        $entities = [];
        for ($i = 1; $i <= 100; $i++) {
            $entities[] = $this->createRateHistory(
                self::getRub()->getCode(),
                self::getUsd()->getCode(),
                (string) (70 + ($i * 0.01)),
                sprintf('2023-01-%02d', ($i % 28) + 1)
            );
        }

        $this->repository->saveBatch($entities);

        $result = $this->repository->findByCurrencyPair(self::getRub(), self::getUsd());
        $this->assertSame(100, $result->getTotalCount());
    }
}

