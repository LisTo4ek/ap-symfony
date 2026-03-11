<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Repository;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrinePageableService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use App\Bundle\CurrencyRateBundle\Src\Storage\CurrentRateStorageInterface;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Doctrine repository for CurrentRate entities.
 *
 * Implements CurrentRateStorageInterface to provide persistence operations
 * including upsert, date-based lookup, and paginated queries for current exchange rates.
 *
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
     * Finds a current rate record for a specific base/target currency pair.
     *
     * @param Currency $baseCurrency   The base currency to search for
     * @param Currency $targetCurrency The target currency to search for
     *
     * @return CurrentRate|null The matching entity, or null if not found
     */
    private function findByCurrencyPair(Currency $baseCurrency, Currency $targetCurrency): ?CurrentRate
    {
        return $this->findOneBy([
            'baseCurrency' => $baseCurrency->getCode(),
            'targetCurrency' => $targetCurrency->getCode(),
        ]);
    }

    /**
     * Inserts a new current rate or updates the existing one for the given currency pair.
     *
     * If a record already exists for the base/target pair, its value and date are updated.
     * Otherwise, a new CurrentRate entity is created and persisted.
     *
     * @param Currency $baseCurrency The base (source) currency
     * @param Currency $targetCurrency The target (destination) currency
     * @param BigDecimal $value The exchange rate value
     * @param DateTimeInterface $date The date the rate applies to
     *
     * @return CurrentRate The persisted (inserted or updated) entity
     */
    public function upsertForCurrencyPair(
        Currency $baseCurrency,
        Currency $targetCurrency,
        BigDecimal $value,
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

    /**
     * Checks whether any current rate records exist for the given date.
     *
     * @param DateTimeImmutable $date The date to check for records
     *
     * @return bool True if at least one record exists for the date
     */
    public function hasRecordsByDay(DateTimeImmutable $date): bool
    {
        return $this->createQueryBuilder('cr')
            ->select('1')
            ->where('cr.date = :date')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    /**
     * Returns the most recent date for which current rate records exist.
     *
     * @return DateTimeImmutable|null The latest date, or null if no records exist
     */
    public function getLatestDate(): ?DateTimeImmutable
    {
        $res = $this
            ->createQueryBuilder('cr')
            ->select('MAX(cr.date)')
            ->getQuery()
            ->getSingleScalarResult();
        if (!is_string($res) || $res === '') {
            return null;
        }

        return new DateTimeImmutable($res);
    }

    /**
     * Returns a pageable query for current rates filtered by date and base currency.
     *
     * Results are ordered by target currency ascending.
     *
     * @param DateTimeImmutable $date The date to filter by
     * @param Currency $baseCurrency The base currency to filter by
     *
     * @return PaginationPageableServiceInterface<CurrentRate> Pageable query adapter for the results
     */
    public function findByDateAndBaseCurrency(
        DateTimeImmutable $date,
        Currency $baseCurrency,
    ): PaginationPageableServiceInterface {
        $queryBuilder = $this->createQueryBuilder('cr')
            ->where('cr.date = :date')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->andWhere('cr.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency->getCode())
            ->orderBy('cr.targetCurrency', 'ASC');

        /** @phpstan-ignore-next-line varTag.nativeType */
        return new PaginationDoctrinePageableService($queryBuilder);
    }
}
