<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;

/**
 * Simple implementation of PaginationResult
 *
 * @template T of object
 * @implements PaginationResultInterface<T>
 */
class PaginationResultContainer implements PaginationResultInterface
{
    /**
     * @param int                       $currentPage The current page number (1-indexed)
     * @param int                       $perPage     Number of items per page
     * @param int                       $totalCount  Total number of items across all pages
     * @param PaginationConfigInterface $config      Pagination configuration (allowed per-page options, etc.)
     * @param int                       $totalPages  Total number of pages
     * @param array<T>                  $items       Items for the current page
     */
    public function __construct(
        private readonly int $currentPage,
        private readonly int $perPage,
        private readonly int $totalCount,
        private readonly PaginationConfigInterface $config,
        private readonly int $totalPages,
        private readonly array $items,
    ) {
    }

    /**
     * Get the current page number.
     *
     * @return int The 1-indexed current page
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get the number of items per page.
     *
     * @return int Items per page count
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the total number of items across all pages.
     *
     * @return int Total item count
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * Get the pagination configuration.
     *
     * @return PaginationConfigInterface The configuration with allowed per-page options
     */
    public function getConfig(): PaginationConfigInterface
    {
        return $this->config;
    }

    /**
     * Get the total number of pages.
     *
     * @return int Total page count
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * @return array<T>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Check whether a next page exists after the current one.
     *
     * @return bool True if current page is less than total pages
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Check whether a previous page exists before the current one.
     *
     * @return bool True if current page is greater than 1
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}
