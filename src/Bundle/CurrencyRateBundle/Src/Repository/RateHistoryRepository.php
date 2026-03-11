<?php

declare(strict_types=1);

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
 * Doctrine repository for RateHistory entities.
 *
 * Implements RateHistoryStorageInterface to provide batch persistence
 * and paginated currency-pair lookups for historical exchange rate data.
 *
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
     * Persists a batch of RateHistory entities within a single database transaction.
     *
     * Does nothing if the array is empty.
     *
     * @param array<RateHistory> $entities The rate history entities to persist
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

    /**
     * Returns a pageable query for rate history records filtered by a currency pair.
     *
     * Results are ordered by date descending, then by ID descending.
     *
     * @param Currency $baseCurrency The base currency to filter by
     * @param Currency $targetCurrency The target currency to filter by
     *
     * @return PaginationPageableServiceInterface<RateHistory> Pageable query adapter for the results
     */
    public function findByCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency
    ): PaginationPageableServiceInterface {
        $queryBuilder = $this->createQueryBuilder('rh')
            ->where('rh.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency->getCode())
            ->andWhere('rh.targetCurrency = :targetCurrency')
            ->setParameter('targetCurrency', $targetCurrency->getCode())
            ->orderBy('rh.date', 'DESC')
            ->addOrderBy('rh.id', 'DESC');

        /** @phpstan-ignore-next-line varTag.nativeType */
        return new PaginationDoctrinePageableService($queryBuilder);
    }
}
