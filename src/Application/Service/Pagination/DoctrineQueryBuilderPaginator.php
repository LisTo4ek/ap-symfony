<?php

declare(strict_types=1);

namespace App\Application\Service\Pagination;

use App\Domain\Contracts\Pagination\PageableContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorContract;

/**
 * Paginator implementation that works with PageableContract
 *
 * Fetches only the requested page using the provided query abstraction
 * Calculates total count efficiently for pagination navigation
 * Preserves generic type information through the pagination chain
 *
 * @template TItem of object
 * @implements PaginatorContract<TItem>
 */
class DoctrineQueryBuilderPaginator implements PaginatorContract
{
    /**
     * @param PageableContract<TItem> $query
     * @return PaginationResultContract<TItem>
     */
    public function paginate(PageableContract $query, int $page = 1, int $itemsPerPage = 10): PaginationResultContract
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        // Get total count and pages from the query
        $totalCount = $query->getTotalCount();
        $totalPages = $query->getTotalPages($itemsPerPage);

        // Ensure page is within bounds
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        // Fetch items for current page
        $items = $query->getPage($page, $itemsPerPage);

        return new PaginationResult(
            currentPage: $page,
            totalCount: $totalCount,
            itemsPerPage: $itemsPerPage,
            totalPages: $totalPages,
            items: $items,
        );
    }
}


