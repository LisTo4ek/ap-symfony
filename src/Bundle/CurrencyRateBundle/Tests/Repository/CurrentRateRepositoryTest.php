<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrinePageableService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrentRateRepositoryTest extends TestCase
{
    use CurrencyTrait;

    private CurrentRateRepository $repository;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->initCurrencies();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $classMetadata = new ClassMetadata(CurrentRate::class);

        $this->entityManager->method('getClassMetadata')
            ->with(CurrentRate::class)
            ->willReturn($classMetadata);

        $this->registry->method('getManagerForClass')
            ->with(CurrentRate::class)
            ->willReturn($this->entityManager);

        $this->repository = new CurrentRateRepository($this->registry);
    }

    public function testImplementsStorageInterface(): void
    {
        $this->assertInstanceOf(CurrentRateStorageInterface::class, $this->repository);
    }

    public function testUpsertForCurrencyPairCreatesNewEntityWhenNotFound(): void
    {
        $value = BigDecimal::of('75.50');
        $date = new DateTimeImmutable('2026-03-06');

        // Mock findOneBy to return null (entity not found)
        $this->entityManager->method('find')->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value,
            $date
        );

        $this->assertInstanceOf(CurrentRate::class, $result);
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

        $existingEntity = new CurrentRate($this->rubCurrency, $this->usdCurrency, $value1, $date1);

        // Mock the repository's internal findOneBy to return existing entity
        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy', 'getEntityManager'])
            ->getMock();

        $mockRepository->method('findOneBy')
            ->willReturn($existingEntity);
        $mockRepository->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $mockRepository->upsertForCurrencyPair(
            $this->rubCurrency,
            $this->usdCurrency,
            $value2,
            $date2
        );

        // Should update existing entity values
        $this->assertSame('80.00', $result->getValue()->toString());
        $this->assertSame('2026-03-06', $result->getDate()->format('Y-m-d'));
    }

    public function testHasRecordsByDayReturnsTrueWhenRecordsExist(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getOneOrNullResult')->willReturn(1);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertTrue($mockRepository->hasRecordsByDay($date));
    }

    public function testHasRecordsByDayReturnsFalseWhenNoRecords(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getOneOrNullResult')->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertFalse($mockRepository->hasRecordsByDay($date));
    }

    public function testGetLatestDateReturnsNullWhenNoRecords(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertNull($mockRepository->getLatestDate());
    }

    public function testGetLatestDateReturnsCorrectDate(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn('2026-03-06');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $latestDate = $mockRepository->getLatestDate();

        $this->assertNotNull($latestDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $latestDate);
        $this->assertSame('2026-03-06', $latestDate->format('Y-m-d'));
    }

    public function testGetLatestDateReturnsNullForEmptyString(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn('');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertNull($mockRepository->getLatestDate());
    }

    public function testFindByDateAndBaseCurrencyReturnsPageableInterface(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $result = $mockRepository->findByDateAndBaseCurrency($date, $this->rubCurrency);

        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
        $this->assertInstanceOf(PaginationDoctrinePageableService::class, $result);
    }

    public function testFindByDateAndBaseCurrencyUsesCorrectParameters(): void
    {
        $date = new DateTimeImmutable('2026-03-06');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('cr.date = :date')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('cr.baseCurrency = :baseCurrency')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('cr.targetCurrency', 'ASC')
            ->willReturnSelf();

        $mockRepository = $this->getMockBuilder(CurrentRateRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('cr')
            ->willReturn($queryBuilder);

        $mockRepository->findByDateAndBaseCurrency($date, $this->rubCurrency);
    }
}

