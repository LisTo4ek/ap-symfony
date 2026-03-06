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
     * @param PaginationPageableServiceInterface<TItem> $pageable
     * @return PaginationResultInterface<TItem>
     */
    public function paginate(PaginationPageableServiceInterface $pageable, PaginationConfigInterface $config, int $perPage, int $page = 1): PaginationResultInterface
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
