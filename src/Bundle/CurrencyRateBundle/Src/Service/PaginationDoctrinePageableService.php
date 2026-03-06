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
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {
    }

    public function getTotalCount(): int
    {
        $countQuery = clone $this->queryBuilder;

        // Remove ORDER BY from count query to avoid PostgreSQL grouping errors
        // when using COUNT(DISTINCT ...) with ORDER BY containing non-grouped columns
        $countQuery->resetDQLPart('orderBy');

        return (int) $countQuery
            ->select('COUNT(DISTINCT ' . $countQuery->getRootAliases()[0] . ')')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<T>
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

        $result = $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        // PHPStan: getResult() returns mixed, but Doctrine ORM guarantees an array
        /** @var array<T> $result */
        return $result;
    }

    public function getTotalPages(int $itemsPerPage): int
    {
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        return (int) ceil($this->getTotalCount() / $itemsPerPage);
    }
}
