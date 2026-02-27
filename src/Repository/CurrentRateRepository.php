<?php

namespace App\Repository;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Entity\CurrentRate;
use DateTimeInterface;
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
    public function findByCurrencyPair(CurrencyContract $baseCurrency, CurrencyContract $targetCurrency): ?CurrentRate
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
            $this->getEntityManager()->persist($entity);
        }

        return $entity;
    }

    /**
     * Get all current rates sorted by base currency
     * @return array<CurrentRate>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['baseCurrency' => 'ASC', 'targetCurrency' => 'ASC']);
    }

    /**
     * Get all rates for a specific base currency
     * @return array<CurrentRate>
     */
    public function findByBaseCurrency(CurrencyContract $baseCurrency): array
    {
        return $this->findBy(['baseCurrency' => $baseCurrency], ['targetCurrency' => 'ASC']);
    }

    /**
     * Get all rates for a specific target currency
     * @return array<CurrentRate>
     */
    public function findByTargetCurrency(CurrencyContract $targetCurrency): array
    {
        return $this->findBy(['targetCurrency' => $targetCurrency], ['baseCurrency' => 'ASC']);
    }
}
