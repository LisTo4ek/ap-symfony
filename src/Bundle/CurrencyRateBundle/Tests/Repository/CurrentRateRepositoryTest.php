<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\KernelTestCase;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use App\Bundle\CurrencyRateBundle\Tests\Trait\DatabaseSetupTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

class CurrentRateRepositoryTest extends KernelTestCase
{
    use CurrencyTrait;
    use DatabaseSetupTrait;

    private CurrentRateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->repository = $this->fromContainer(CurrentRateRepository::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
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
            self::getRub(),
            self::getUsd(),
            $value,
            $date
        );

        $this->assertInstanceOf(CurrentRate::class, $result);
        $this->assertNotNull($result->getId());
        $this->assertSame(self::getRub()->getCode(), $result->getBaseCurrency()->getCode());
        $this->assertSame(self::getUsd()->getCode(), $result->getTargetCurrency()->getCode());
        $this->assertSame('75.50', $result->getValue()->toString());
        $this->assertSame('2026-03-06', $result->getDate()->format('Y-m-d'));
    }

    public function testUpsertForCurrencyPairUpdatesExistingEntity(): void
    {
        $value1 = BigDecimal::of('75.50');
        $value2 = BigDecimal::of('80.00');
        $date1 = new DateTimeImmutable('2026-03-05');
        $date2 = new DateTimeImmutable('2026-03-06');
        $created = $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            $value1,
            $date1
        );
        $originalId = $created->getId();
        $updated = $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            $value2,
            $date2
        );

        $this->assertSame($originalId, $updated->getId());
        $this->assertSame('80.00', $updated->getValue()->toString());
        $this->assertSame('2026-03-06', $updated->getDate()->format('Y-m-d'));
    }

    public function testUpsertForDifferentCurrencyPairsCreatesSeparateEntities(): void
    {
        $value = BigDecimal::of('75.50');
        $date = new DateTimeImmutable('2026-03-06');

        $rubUsd = $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            $value,
            $date
        );

        $rubEur = $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getEur(),
            BigDecimal::of('85.00'),
            $date
        );

        $this->assertNotSame($rubUsd->getId(), $rubEur->getId());
    }

    public function testHasRecordsByDayReturnsTrueWhenRecordsExist(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
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
            self::getRub(),
            self::getUsd(),
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

        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            BigDecimal::of('75.50'),
            $date1
        );
        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getEur(),
            BigDecimal::of('85.00'),
            $date2
        );
        $this->repository->upsertForCurrencyPair(
            self::getUsd(),
            self::getEur(),
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

        $result = $this->repository->findByDateAndBaseCurrency($date, self::getRub());

        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
    }

    public function testFindByDateAndBaseCurrencyReturnsEmptyWhenNoMatch(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $result = $this->repository->findByDateAndBaseCurrency($date, self::getRub());

        $this->assertSame(0, $result->getTotalCount());
        $this->assertEmpty($result->getPage(1, 10));
    }

    public function testFindByDateAndBaseCurrencyReturnsMatchingRates(): void
    {
        $date = new DateTimeImmutable('2026-03-06');
        $otherDate = new DateTimeImmutable('2026-03-05');

        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            BigDecimal::of('75.50'),
            $date
        );

        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getEur(),
            BigDecimal::of('85.00'),
            $date
        );

        $this->repository->upsertForCurrencyPair(
            self::getEur(),
            self::getUsd(),
            BigDecimal::of('1.08'),
            $otherDate
        );

        $this->repository->upsertForCurrencyPair(
            self::getUsd(),
            self::getEur(),
            BigDecimal::of('1.10'),
            $date
        );

        $result = $this->repository->findByDateAndBaseCurrency($date, self::getRub());

        $this->assertSame(2, $result->getTotalCount());
        $items = $result->getPage(1, 10);
        $this->assertCount(2, $items);
    }

    public function testFindByDateAndBaseCurrencyOrdersByTargetCurrency(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getUsd(),
            BigDecimal::of('75.50'),
            $date
        );
        $this->repository->upsertForCurrencyPair(
            self::getRub(),
            self::getEur(),
            BigDecimal::of('85.00'),
            $date
        );

        $result = $this->repository->findByDateAndBaseCurrency($date, self::getRub());
        $items = $result->getPage(1, 10);

        $this->assertSame(self::getEur()->getCode(), $items[0]->getTargetCurrency()->getCode());
        $this->assertSame(self::getUsd()->getCode(), $items[1]->getTargetCurrency()->getCode());
    }
}
