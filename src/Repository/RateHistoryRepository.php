<?php

namespace App\Repository;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use App\Entity\RateHistory;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use function array_map;

/**
 * @extends ServiceEntityRepository<RateHistory>
 */
#[AsAlias(RateHistoryStorageContract::class)]
class RateHistoryRepository extends ServiceEntityRepository implements RateHistoryStorageContract
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RateHistory::class);
    }

    /**
     * @param array<CurrencyContract> $codes
     * @return array<RateHistory>
     */
    private function findByCurrencyAndDate(array $codes, DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('rh')
            ->andWhere('rh.charCode IN (:codes)')
            ->andWhere('rh.date = :date')
            ->setParameter('codes', array_map(static fn (CurrencyContract $c) => $c->getCode(), $codes))
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

        foreach ($entities as $entity) {
            $em->persist($entity);
        }

        $em->flush();
    }


    public function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): QueryBuilder
    {
        return $this->createQueryBuilder('rh')
            ->where('rh.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->andWhere('rh.targetCurrency = :targetCurrency')
            ->setParameter('targetCurrency', $targetCurrency)
            ->orderBy('rh.date', 'DESC')
            ;
    }
}
