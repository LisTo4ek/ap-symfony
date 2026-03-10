<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;

/**
 * Paginator implementation that works with PageableContract
 *
 * Fetches only the requested page using the provided query abstraction
 * Calculates total count efficiently for pagination navigation
 * Preserves generic type information through the pagination chain
 *
 * @template TItem of object
 * @implements PaginationServiceInterface<TItem>
 */
class PaginationDoctrineService implements PaginationServiceInterface
{
    /**
     * Paginates a pageable query, returning a result container with items and metadata.
     *
     * Clamps the page number to valid bounds (1 … totalPages).
     * Fetches only the items for the requested page via the pageable abstraction.
     *
     * @param PaginationPageableServiceInterface<TItem> $pageable The query abstraction to paginate
     * @param PaginationConfigInterface                 $config   Pagination configuration (per-page options, etc.)
     * @param int                                       $perPage  Number of items per page
     * @param int                                       $page     The requested page number (1-indexed, default 1)
     *
     * @return PaginationResultInterface<TItem> The paginated result with items, counts, and page info
     */
    public function paginate(
        PaginationPageableServiceInterface $pageable,
        PaginationConfigInterface $config,
        int $perPage,
        int $page = 1,
    ): PaginationResultInterface {
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

        return new PaginationResultContainer(
            currentPage: $page,
            perPage: $perPage,
            totalCount: $totalCount,
            config: $config,
            totalPages: $totalPages,
            items: $items,
        );
    }
}
