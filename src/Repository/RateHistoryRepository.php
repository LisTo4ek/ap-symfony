<?php

namespace App\Repository;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Entity\RateHistory;
use DateTimeImmutable;
use DateTimeInterface;
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

    public function findByCurrencyPairAndDate(
        CurrencyEnum $baseCurrency,
        CurrencyEnum $targetCurrency,
        DateTimeInterface $date
    ): ?RateHistory {
        return $this->createQueryBuilder('rh')
            ->andWhere('rh.base_currency = :baseCurrency')
            ->andWhere('rh.target_currency = :targetCurrency')
            ->andWhere('rh.date = :date')
            ->setParameter('baseCurrency', $baseCurrency)
            ->setParameter('targetCurrency', $targetCurrency)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<RateHistory>
     */
    public function findByCurrencyPairAndDateRange(
        CurrencyEnum $baseCurrency,
        ?CurrencyEnum $targetCurrency,
        DateTimeInterface $from,
        DateTimeInterface $to,
    ): array {
        $query = $this->createQueryBuilder('rh')
            ->andWhere('rh.base_currency = :baseCurrency')
            ->andWhere('rh.date BETWEEN :from AND :to')
            ->setParameter('baseCurrency', $baseCurrency)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('rh.date', 'ASC');

        if ($targetCurrency) {
            $query->andWhere('rh.target_currency = :targetCurrency')
                ->setParameter('targetCurrency', $targetCurrency);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    public function updateOrCreate(
        CurrencyEnum $baseCurrency,
        CurrencyEnum $targetCurrency,
        string $value,
        DateTimeInterface $date
    ): RateHistory {
        $entity = $this->findByCurrencyPairAndDate($baseCurrency, $targetCurrency, $date);

        if ($entity) {
            $entity->setValue($value);
        } else {
            $entity = new RateHistory($baseCurrency, $targetCurrency, $value, $date);
            $this->getEntityManager()->persist($entity);
        }

        return $entity;
    }
//
//    /**
//     * @param Rate[] $rates
//     */
//    public function upsertForDate(array $rates, DateTimeInterface $date): void
//    {
//        if ($rates === []) {
//            return;
//        }
//
//        $em = $this->getEntityManager();
//
//        $em->wrapInTransaction(function () use ($rates, $date, $em): void {
//            $codes = array_values(array_unique(array_map(
//                static fn(Rate $rate): CurrencyEnum => $rate->targetCurrency,
//                $rates
//            )));
//
//            $existing = $this->findByCurrencyAndDate($codes, $date);
//            $existingByCode = [];
//
//            foreach ($existing as $entity) {
//                $existingByCode[$entity->getBaseCurrency()->value] = $entity;
//            }
//
//            foreach ($rates as $rate) {
//                $code = $rate->targetCurrency->value;
//
//                if (isset($existingByCode[$code])) {
//                    $entity = $existingByCode[$code];
//                    $entity->setValue((string)$rate->rate);
//                    continue;
//                }
//
//                $entity = new RateHistory(
//                    $rate->targetCurrency,
//                    (string)$rate->rate,
//                    DateTimeImmutable::createFromInterface($date)
//                );
//                $em->persist($entity);
//                $existingByCode[$code] = $entity;
//            }
//
//            $em->flush();
//        });
//    }

    /**
     * @param CurrencyEnum[] $codes
     * @return array<RateHistory>
     */
    private function findByCurrencyAndDate(array $codes, DateTimeInterface $date): array
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
     * @param array<RateHistory> $entities
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
