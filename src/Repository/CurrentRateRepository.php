<?php

namespace App\Repository;

use App\Application\Service\Pagination\DoctrinePageable;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\Contracts\Pagination\PageableContract;
use App\Entity\CurrentRate;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<CurrentRate>
 */
#[AsAlias(CurrentRateStorageContract::class)]
class CurrentRateRepository extends ServiceEntityRepository implements CurrentRateStorageContract
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, CurrentRate::class);
    }

    /**
     * Find rate for a specific currency pair
     */
    private function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): ?CurrentRate
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
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
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

    public function findByDateAndBaseCurrency(DateTimeImmutable $date, CurrencyContract $baseCurrency): PageableContract
    {
        $queryBuilder = $this->createQueryBuilder('cr')
            ->where('cr.date = :date')
            ->setParameter('date', $date)
            ->andWhere('cr.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->orderBy('cr.targetCurrency', 'ASC');

        return new DoctrinePageable($queryBuilder);
    }
}
