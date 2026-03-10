<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\KernelTestCase;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class CurrentRateRepositoryTest extends KernelTestCase
{
    use CurrencyTrait;

    private CurrentRateRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initCurrencies();

        $this->repository = $this->fromContainer(CurrentRateRepository::class);
        $this->entityManager = $this->fromContainer(EntityManagerInterface::class);

        $this->clearTable();
    }

    protected function tearDown(): void
    {
        $this->clearTable();
        parent::tearDown();
    }

    private function clearTable(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM current_rate');
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(CurrentRateStorageInterface::class, $this->repository);
    }

    public function testUpsertForCurrencyPairCreatesNewEntity(): void
    {
        $value = BigDecimal::of('75.50');
        $date = new DateTimeImmutable('2026-03-06');

        $result = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value,
            $date
        );

        $this->assertInstanceOf(CurrentRate::class, $result);
        $this->assertNotNull($result->getId());
        $this->assertSame('RUB', $result->getBaseCurrency()->getCode());
        $this->assertSame('USD', $result->getTargetCurrency()->getCode());
        $this->assertSame('75.50', $result->getValue()->toString());
        $this->assertSame('2026-03-06', $result->getDate()->format('Y-m-d'));
    }

    public function testUpsertForCurrencyPairUpdatesExistingEntity(): void
    {
        $value1 = BigDecimal::of('75.50');
        $value2 = BigDecimal::of('80.00');
        $date1 = new DateTimeImmutable('2026-03-05');
        $date2 = new DateTimeImmutable('2026-03-06');

        // Create initial entity
        $created = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value1,
            $date1
        );
        $originalId = $created->getId();

        // Update with same currency pair
        $updated = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value2,
            $date2
        );

        // Should be the same entity with updated values
        $this->assertSame($originalId, $updated->getId());
        $this->assertSame('80.00', $updated->getValue()->toString());
        $this->assertSame('2026-03-06', $updated->getDate()->format('Y-m-d'));
    }

    public function testUpsertForDifferentCurrencyPairsCreatesSeparateEntities(): void
    {
        $value = BigDecimal::of('75.50');
        $date = new DateTimeImmutable('2026-03-06');

        $rubUsd = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value,
            $date
        );

        $rubEur = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->eurCurrency,
            BigDecimal::of('85.00'),
            $date
        );

        $this->assertNotSame($rubUsd->getId(), $rubEur->getId());
    }

    public function testHasRecordsByDayReturnsTrueWhenRecordsExist(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('75.50'),
            $date
        );

        $this->assertTrue($this->repository->hasRecordsByDay($date));
    }

    public function testHasRecordsByDayReturnsFalseWhenNoRecords(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $this->assertFalse($this->repository->hasRecordsByDay($date));
    }

    public function testHasRecordsByDayReturnsFalseForDifferentDate(): void
    {
        $date1 = new DateTimeImmutable('2026-03-05');
        $date2 = new DateTimeImmutable('2026-03-06');

        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('75.50'),
            $date1
        );

        $this->assertFalse($this->repository->hasRecordsByDay($date2));
    }

    public function testGetLatestDateReturnsNullWhenNoRecords(): void
    {
        $this->assertNull($this->repository->getLatestDate());
    }

    public function testGetLatestDateReturnsCorrectDate(): void
    {
        $date1 = new DateTimeImmutable('2026-03-05');
        $date2 = new DateTimeImmutable('2026-03-06');
        $date3 = new DateTimeImmutable('2026-03-04');

        // Insert in random order
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('75.50'),
            $date1
        );
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->eurCurrency,
            BigDecimal::of('85.00'),
            $date2
        );
        $this->repository->upsertForCurrencyPair(
            $this->usdCurrency,
            $this->eurCurrency,
            BigDecimal::of('1.10'),
            $date3
        );

        $latestDate = $this->repository->getLatestDate();

        $this->assertNotNull($latestDate);
        $this->assertSame('2026-03-06', $latestDate->format('Y-m-d'));
    }

    public function testFindByDateAndBaseCurrencyReturnsPageableInterface(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $result = $this->repository->findByDateAndBaseCurrency($date, $this->rubCurrency);

        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
    }

    public function testFindByDateAndBaseCurrencyReturnsEmptyWhenNoMatch(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $result = $this->repository->findByDateAndBaseCurrency($date, $this->rubCurrency);

        $this->assertSame(0, $result->getTotalCount());
        $this->assertEmpty($result->getPage(1, 10));
    }

    public function testFindByDateAndBaseCurrencyReturnsMatchingRates(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $otherDate = new DateTimeImmutable('2026-03-05');

        // Create rates for the target date and base currency
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('75.50'),
            $date
        );
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->eurCurrency,
            BigDecimal::of('85.00'),
            $date
        );

        // Create rates that should NOT match (different date)
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('76.00'),
            $otherDate
        );

        // Create rates that should NOT match (different base currency)
        $this->repository->upsertForCurrencyPair(
            $this->usdCurrency,
            $this->eurCurrency,
            BigDecimal::of('1.10'),
            $date
        );

        $result = $this->repository->findByDateAndBaseCurrency($date, $this->rubCurrency);

        $this->assertSame(2, $result->getTotalCount());
        $items = $result->getPage(1, 10);
        $this->assertCount(2, $items);
    }

    public function testFindByDateAndBaseCurrencyOrdersByTargetCurrency(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        // Insert in reverse order to verify sorting
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            BigDecimal::of('75.50'),
            $date
        );
        $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->eurCurrency,
            BigDecimal::of('85.00'),
            $date
        );

        $result = $this->repository->findByDateAndBaseCurrency($date, $this->rubCurrency);
        $items = $result->getPage(1, 10);

        // EUR should come before USD alphabetically
        $this->assertSame('EUR', $items[0]->getTargetCurrency()->getCode());
        $this->assertSame('USD', $items[1]->getTargetCurrency()->getCode());
    }
}

