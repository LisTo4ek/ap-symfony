<?php

declare(strict_types=1);

namespace App\Application\Service\Pagination;

use App\Domain\Contracts\Pagination\ItemsPerPageContract;
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
     * @param PageableContract<TItem> $pageable
     * @return PaginationResultContract<TItem>
     */
    public function paginate(PageableContract $pageable, ItemsPerPageContract $itemsPerPage, int $page = 1): PaginationResultContract
    {
        if ($page < 1) {
            $page = 1;
        }

        // Get total count and pages from the query
        $totalCount = $pageable->getTotalCount();
        $totalPages = $pageable->getTotalPages($itemsPerPage->getPerPage());

        // Ensure page is within bounds
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        // Fetch items for current page
        $items = $pageable->getPage($page, $itemsPerPage->getPerPage());

        return new PaginationResult(
            currentPage: $page,
            totalCount: $totalCount,
            itemsPerPage: $itemsPerPage,
            totalPages: $totalPages,
            items: $items,
        );
    }
}


