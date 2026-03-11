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
     * Get the total count of items matching the query.
     *
     * @return int Total number of matching items
     */
    public function getTotalCount(): int;

    /**
     * Get items for a specific page.
     *
     * @param int $page The 1-indexed page number
     * @param int $itemsPerPage Number of items to fetch per page
     *
     * @return array<T> The items for the requested page
     */
    public function getPage(int $page, int $itemsPerPage): array;

    /**
     * Get total number of pages for the given items per page.
     *
     * @param int $itemsPerPage Number of items per page
     *
     * @return int Total page count
     */
    public function getTotalPages(int $itemsPerPage): int;
}
