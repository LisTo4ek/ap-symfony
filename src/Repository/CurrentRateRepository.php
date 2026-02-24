<?php

namespace App\Repository;

use App\RateProvider\Domain\ValueObject\Currency;
use App\Entity\CurrentRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CurrentRate>
 */
class CurrentRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrentRate::class);
    }

    public function save(CurrentRate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find rate for a specific currency pair
     */
    public function findByCurrencyPair(Currency $baseCurrency, Currency $targetCurrency): ?CurrentRate
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
        string $value
    ): CurrentRate {
        $entity = $this->findByCurrencyPair($baseCurrency, $targetCurrency);

        if ($entity) {
            $entity->setValue($value);
        } else {
            $entity = new CurrentRate($baseCurrency, $targetCurrency, $value);
            $this->getEntityManager()->persist($entity);
        }

        return $entity;
    }

    /**
     * Get all current rates sorted by base currency
     * @return CurrentRate[]
     */
    public function findAll(): array
    {
        return $this->findBy([], ['baseCurrency' => 'ASC', 'targetCurrency' => 'ASC']);
    }

    /**
     * Get all rates for a specific base currency
     * @return CurrentRate[]
     */
    public function findByBaseCurrency(Currency $baseCurrency): array
    {
        return $this->findBy(['baseCurrency' => $baseCurrency], ['targetCurrency' => 'ASC']);
    }

    /**
     * Get all rates for a specific target currency
     * @return CurrentRate[]
     */
    public function findByTargetCurrency(Currency $targetCurrency): array
    {
        return $this->findBy(['targetCurrency' => $targetCurrency], ['baseCurrency' => 'ASC']);
    }
}

