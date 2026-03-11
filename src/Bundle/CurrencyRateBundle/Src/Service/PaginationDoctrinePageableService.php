<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use Doctrine\ORM\QueryBuilder;

/**
 * Doctrine QueryBuilder adapter for PageableContract
 *
 * Allows QueryBuilder to be used through the PageableContract abstraction
 * Supports generic typing for different entity types
 *
 * @template T of object
 * @implements PaginationPageableServiceInterface<T>
 *
 * @property QueryBuilder $queryBuilder The Doctrine QueryBuilder representing the base query
 */
class PaginationDoctrinePageableService implements PaginationPageableServiceInterface
{
    /** @var int|null Cached total item count to avoid repeated COUNT queries */
    private ?int $cachedTotalCount = null;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        if ($this->cachedTotalCount !== null) {
            return $this->cachedTotalCount;
        }

        $countQb = clone $this->queryBuilder;
        $countQb->resetDQLPart('orderBy');
        $countQb->select('COUNT(DISTINCT ' . $countQb->getRootAliases()[0] . ')');
        $this->cachedTotalCount = (int) $countQb
            ->getQuery()
            ->getSingleScalarResult();

        return $this->cachedTotalCount;
    }

    /**
     * @inheritDoc
     */
    public function getPage(int $page, int $itemsPerPage): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        $offset = ($page - 1) * $itemsPerPage;
        $qb = clone $this->queryBuilder;
        $result = $qb
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        /** @var array<T> $result */
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getTotalPages(int $itemsPerPage): int
    {
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        return (int) ceil($this->getTotalCount() / $itemsPerPage);
    }
}
