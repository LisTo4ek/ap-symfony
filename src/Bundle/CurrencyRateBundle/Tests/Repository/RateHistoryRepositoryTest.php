<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Repository\RateHistoryRepository;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrinePageableService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateHistoryRepositoryTest extends TestCase
{
    use CurrencyTrait;

    private RateHistoryRepository $repository;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->initCurrencies();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $classMetadata = new ClassMetadata(RateHistory::class);

        $this->entityManager->method('getClassMetadata')
            ->with(RateHistory::class)
            ->willReturn($classMetadata);

        $this->registry->method('getManagerForClass')
            ->with(RateHistory::class)
            ->willReturn($this->entityManager);

        $this->repository = new RateHistoryRepository($this->registry);
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
        // wrapInTransaction should not be called with empty array
        $this->entityManager->expects($this->never())->method('wrapInTransaction');

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $mockRepository->method('getEntityManager')->willReturn($this->entityManager);

        $mockRepository->saveBatch([]);
    }

    public function testSaveBatchPersistsSingleEntity(): void
    {
        $entity = $this->createRateHistory('RUB', 'USD', '75.50', '2026-03-06');

        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                $callback();
            });

        $this->entityManager->expects($this->once())->method('persist')->with($entity);
        $this->entityManager->expects($this->once())->method('flush');

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $mockRepository->method('getEntityManager')->willReturn($this->entityManager);

        $mockRepository->saveBatch([$entity]);
    }

    public function testSaveBatchPersistsMultipleEntities(): void
    {
        $entities = [
            $this->createRateHistory('RUB', 'USD', '75.50', '2026-03-06'),
            $this->createRateHistory('RUB', 'USD', '76.00', '2026-03-05'),
            $this->createRateHistory('RUB', 'USD', '74.50', '2026-03-04'),
        ];

        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) {
                $callback();
            });

        $this->entityManager->expects($this->exactly(3))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $mockRepository->method('getEntityManager')->willReturn($this->entityManager);

        $mockRepository->saveBatch($entities);
    }

    public function testSaveBatchWrapsInTransaction(): void
    {
        $entity = $this->createRateHistory('RUB', 'USD', '75.50', '2026-03-06');

        $transactionCallbackExecuted = false;

        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(function (callable $callback) use (&$transactionCallbackExecuted) {
                $transactionCallbackExecuted = true;
                $callback();
            });

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $mockRepository->method('getEntityManager')->willReturn($this->entityManager);

        $mockRepository->saveBatch([$entity]);

        $this->assertTrue($transactionCallbackExecuted);
    }

    public function testFindByCurrencyPairReturnsPageableInterface(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $result = $mockRepository->findByCurrencyPair($this->rubCurrency, $this->usdCurrency);

        $this->assertInstanceOf(PaginationPageableServiceInterface::class, $result);
        $this->assertInstanceOf(PaginationDoctrinePageableService::class, $result);
    }

    public function testFindByCurrencyPairUsesCorrectParameters(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('rh.baseCurrency = :baseCurrency')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('rh.targetCurrency = :targetCurrency')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('rh.date', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('rh.id', 'DESC')
            ->willReturnSelf();

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('rh')
            ->willReturn($queryBuilder);

        $mockRepository->findByCurrencyPair($this->rubCurrency, $this->usdCurrency);
    }

    public function testFindByCurrencyPairOrdersByDateDescending(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();

        // Verify ordering is DESC by date
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('rh.date', 'DESC')
            ->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $mockRepository->findByCurrencyPair($this->rubCurrency, $this->usdCurrency);
    }

    public function testFindByCurrencyPairHasSecondaryOrderById(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();

        // Verify secondary ordering is by id DESC
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('rh.id', 'DESC')
            ->willReturnSelf();

        $mockRepository = $this->getMockBuilder(RateHistoryRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $mockRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $mockRepository->findByCurrencyPair($this->rubCurrency, $this->usdCurrency);
    }
}

