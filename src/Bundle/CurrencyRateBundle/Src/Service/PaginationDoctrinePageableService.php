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
    private ?int $cachedTotalCount = null;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {
    }

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
