<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Pagination;

/**
 * Paginator abstraction that doesn't depend on specific libraries or HTTP requests
 *
 * This interface allows pagination to be decoupled from KnpPaginator and QueryBuilder
 * Works with PageableContract to fetch pages efficiently from any data source
 *
 * @template T of object
 */
interface PaginatorContract
{
    /**
     * Paginate a query
     *
     * @template TItem of object
     * @param PageableContract<TItem> $pageable Query abstraction that provides pagination support
     * @param int $page Current page (1-indexed)
     * @param ItemsPerPageContract $itemsPerPage Number of items per page configuration
     * @return PaginationResultContract<TItem>
     */
    public function paginate(PageableContract $pageable, ItemsPerPageContract $itemsPerPage, int $page = 1): PaginationResultContract;
}







