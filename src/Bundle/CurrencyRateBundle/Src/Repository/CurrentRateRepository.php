<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrinePageableService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<CurrentRate>
 */
#[AsAlias(CurrentRateStorageInterface::class)]
class CurrentRateRepository extends ServiceEntityRepository implements CurrentRateStorageInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, CurrentRate::class);
    }

    /**
     * Find rate for a specific currency pair
     */
    private function findByCurrencyPair(Currency $baseCurrency, Currency $targetCurrency): ?CurrentRate
    {
        return $this->findOneBy([
            'baseCurrency' => $baseCurrency,
            'targetCurrency' => $targetCurrency,
        ]);
    }

    /**
     * Upsert (insert or update) rate for currency pair
     */
    public function upsertForCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
        string $value,
        DateTimeInterface $date,
    ): CurrentRate {
        $entity = $this->findByCurrencyPair($baseCurrency, $targetCurrency);

        if ($entity) {
            $entity->setValue($value);
            $entity->setDate($date);
        } else {
            $entity = new CurrentRate($baseCurrency, $targetCurrency, $value, $date);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity;
    }

    public function hasRecordsByDay(DateTimeImmutable $date): bool
    {
        return $this->createQueryBuilder('cr')
            ->select('1')
            ->where('cr.date = :date')
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function getLatestDate(): ?DateTimeImmutable
    {
        $res = $this
            ->createQueryBuilder('cr')
            ->select('MAX(cr.date)')
            ->getQuery()
            ->getSingleScalarResult();

        return $res ? new DateTimeImmutable($res) : null;
    }

    public function findByDateAndBaseCurrency(DateTimeImmutable $date, Currency $baseCurrency): PaginationPageableServiceInterface
    {
        $queryBuilder = $this->createQueryBuilder('cr')
            ->where('cr.date = :date')
            ->setParameter('date', $date)
            ->andWhere('cr.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->orderBy('cr.targetCurrency', 'ASC');

        return new PaginationDoctrinePageableService($queryBuilder);
    }
}
