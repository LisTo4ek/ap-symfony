<?php

namespace App\Repository;

use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use App\Domain\Contracts\Pagination\PageableContract;
use App\Domain\Service\Pagination\DoctrinePageable;
use App\Entity\RateHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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

    public function findByCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency
    ): PageableContract {
        $queryBuilder = $this->createQueryBuilder('rh')
            ->where('rh.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->andWhere('rh.targetCurrency = :targetCurrency')
            ->setParameter('targetCurrency', $targetCurrency)
            ->orderBy('rh.date', 'DESC')
            ->orderBy('rh.id', 'DESC');

        return new DoctrinePageable($queryBuilder);
    }
}
