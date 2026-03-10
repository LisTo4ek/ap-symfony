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
 */
class PaginationDoctrinePageableService implements PaginationPageableServiceInterface
{
    /** @var int|null Cached total item count to avoid repeated COUNT queries */
    private ?int $cachedTotalCount = null;

    /**
     * @param QueryBuilder $queryBuilder The Doctrine QueryBuilder representing the base query
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {
    }

    /**
     * Returns the total number of items matching the query.
     *
     * The result is cached after the first call to avoid duplicate COUNT queries.
     * Clones the QueryBuilder internally so the original is not mutated.
     *
     * @return int Total item count
     */
    public function getTotalCount(): int
    {
        if ($this->cachedTotalCount !== null) {
            return $this->cachedTotalCount;
        }

        // clone is safe here — Doctrine's QueryBuilder::__clone() deep-clones
        // all DQL part expression objects and creates a fresh ArrayCollection
        // with cloned parameters, so mutations on the clone cannot affect the original.
        $countQb = clone $this->queryBuilder;
        $countQb->resetDQLPart('orderBy');
        $countQb->select('COUNT(DISTINCT ' . $countQb->getRootAliases()[0] . ')');

        $this->cachedTotalCount = (int) $countQb
            ->getQuery()
            ->getSingleScalarResult();

        return $this->cachedTotalCount;
    }

    /**
     * Fetches items for a specific page from the query.
     *
     * Clones the QueryBuilder to apply OFFSET/LIMIT without mutating the original.
     * Clamps page to minimum 1 and itemsPerPage to minimum 10.
     *
     * @param int $page         The 1-indexed page number
     * @param int $itemsPerPage Number of items to fetch per page
     *
     * @return array<T> The items for the requested page
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

        // PHPStan: getResult() returns mixed, but Doctrine ORM guarantees an array
        /** @var array<T> $result */
        return $result;
    }

    /**
     * Calculates the total number of pages for the given items-per-page count.
     *
     * Clamps itemsPerPage to minimum 10 to prevent division by zero.
     *
     * @param int $itemsPerPage Number of items per page
     *
     * @return int Total page count (rounded up)
     */
    public function getTotalPages(int $itemsPerPage): int
    {
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        return (int) ceil($this->getTotalCount() / $itemsPerPage);
    }
}
