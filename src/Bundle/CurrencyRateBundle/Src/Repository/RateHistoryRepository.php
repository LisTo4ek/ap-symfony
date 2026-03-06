<?php

namespace App\Bundle\CurrencyRateBundle\Src\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrinePageableService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<RateHistory>
 */
#[AsAlias(RateHistoryStorageInterface::class)]
class RateHistoryRepository extends ServiceEntityRepository implements RateHistoryStorageInterface
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
    ): PaginationPageableServiceInterface {
        $queryBuilder = $this->createQueryBuilder('rh')
            ->where('rh.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->andWhere('rh.targetCurrency = :targetCurrency')
            ->setParameter('targetCurrency', $targetCurrency)
            ->orderBy('rh.date', 'DESC')
            ->orderBy('rh.id', 'DESC');

        return new PaginationDoctrinePageableService($queryBuilder);
    }
}
