<?php

namespace App\Repository;

use App\RateProvider\Domain\Entity\Rate;
use App\RateProvider\Domain\ValueObject\Currency;
use App\Entity\RateHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RateHistory>
 */
class RateHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RateHistory::class);
    }

    public function save(RateHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCharCodeAndDate(Currency $currency, \DateTimeImmutable $date): ?RateHistory
    {
        return $this->createQueryBuilder('rh')
            ->andWhere('rh.charCode = :code')
            ->andWhere('rh.date = :date')
            ->setParameter('code', $currency->value)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return RateHistory[]
     */
    public function findByCharCodeAndDateRange(
        Currency $currency,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return $this->createQueryBuilder('rh')
            ->andWhere('rh.charCode = :code')
            ->andWhere('rh.date BETWEEN :from AND :to')
            ->setParameter('code', $currency->value)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('rh.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function updateOrCreate(
        Currency $currency,
        string $value,
        \DateTimeImmutable $date
    ): RateHistory {
        $entity = $this->findByCharCodeAndDate($currency, $date);

        if ($entity) {
            $entity->setValue($value);
        } else {
            $entity = new RateHistory($currency, $value, $date);
            $this->getEntityManager()->persist($entity);
        }

        return $entity;
    }

    /**
     * @param Rate[] $rates
     */
    public function upsertForDate(array $rates, \DateTimeImmutable $date): void
    {
        if ($rates === []) {
            return;
        }

        $em = $this->getEntityManager();

        $em->wrapInTransaction(function () use ($rates, $date, $em): void {
            $codes = array_values(array_unique(array_map(
                static fn (Rate $rate): Currency => $rate->targetCurrency,
                $rates
            )));

            $existing = $this->findByCurrencyAndDate($codes, $date);
            $existingByCode = [];

            foreach ($existing as $entity) {
                $existingByCode[$entity->getBaseCurrency()->value] = $entity;
            }

            foreach ($rates as $rate) {
                $code = $rate->targetCurrency->value;

                if (isset($existingByCode[$code])) {
                    $entity = $existingByCode[$code];
                    $entity->setValue((string) $rate->value);
                    continue;
                }

                $entity = new RateHistory(
                    $rate->targetCurrency,
                    (string) $rate->value,
                    \DateTimeImmutable::createFromInterface($date)
                );
                $em->persist($entity);
                $existingByCode[$code] = $entity;
            }

            $em->flush();
        });
    }

    /**
     * @param Currency[] $codes
     * @return RateHistory[]
     */
    private function findByCurrencyAndDate(array $codes, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('rh')
            ->andWhere('rh.charCode IN (:codes)')
            ->andWhere('rh.date = :date')
            ->setParameter('codes', $codes)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param RateHistory[] $entities
     */
    public function saveBatch(array $entities): void
    {
        if ($entities === []) {
            return;
        }

        $em = $this->getEntityManager();

        $em->wrapInTransaction(function () use ($entities, $em): void {
            foreach ($entities as $entity) {
                $em->persist($entity);
            }
            $em->flush();
        });
    }
}
