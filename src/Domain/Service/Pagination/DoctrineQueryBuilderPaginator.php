<?php

declare(strict_types=1);

namespace App\Domain\Service\Pagination;

use App\Domain\Contracts\Pagination\PageableContract;
use App\Domain\Contracts\Pagination\PaginationResultContract;
use App\Domain\Contracts\Pagination\PaginatorConfigContract;
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
    public function paginate(PageableContract $pageable, PaginatorConfigContract $config, int $perPage, int $page = 1): PaginationResultContract
    {
        if ($page < 1) {
            $page = 1;
        }

        // Get total count and pages from the query
        $totalCount = $pageable->getTotalCount();
        $totalPages = $pageable->getTotalPages($perPage);

        // Ensure page is within bounds
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        // Fetch items for current page
        $items = $pageable->getPage($page, $perPage);

        return new PaginationResult(
            currentPage: $page,
            perPage: $perPage,
            totalCount: $totalCount,
            config: $config,
            totalPages: $totalPages,
            items: $items,
        );
    }
}
