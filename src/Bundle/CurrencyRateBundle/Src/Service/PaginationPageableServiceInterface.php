<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

/**
 * Abstraction for a pageable query result
 *
 * Decouples from Doctrine QueryBuilder
 * The paginator will use this to fetch pages without knowing about ORM implementation
 *
 * @template T of object
 */
interface PaginationPageableServiceInterface
{
    /**
     * Get the total count of items matching the query
     */
    public function getTotalCount(): int;

    /**
     * Get items for a specific page
     *
     * @return array<T>
     */
    public function getPage(int $page, int $itemsPerPage): array;

    /**
     * Get total number of pages for the given items per page
     */
    public function getTotalPages(int $itemsPerPage): int;
}



