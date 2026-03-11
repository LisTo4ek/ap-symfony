<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;

/**
 * Represents a paginated collection of items
 *
 * Abstraction over KnpPaginator to decouple from pagination implementation
 *
 * @template T
 */
interface PaginationResultInterface
{
    /**
     * Get current page number
     *
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * Get items per page count
     *
     * @return int Items per page count
     */
    public function getPerPage(): int;

    /**
     * Get total number of items
     *
     * @return int Total item count
     */
    public function getTotalCount(): int;

    /**
     * Get the pagination configuration.
     *
     * @return PaginationConfigInterface The configuration with allowed per-page options
     */
    public function getConfig(): PaginationConfigInterface;

    /**
     * Get the total number of pages.
     *
     * @return int Total page count
     */
    public function getTotalPages(): int;

    /**
     * Get items for current page
     *
     * @return array<T>
     */
    public function getItems(): array;

    /**
     * Check whether a next page exists after the current one.
     *
     * @return bool True if current page is less than total pages
     */
    public function hasNextPage(): bool;

    /**
     * Check whether a previous page exists before the current one.
     *
     * @return bool True if current page is greater than 1
     */
    public function hasPreviousPage(): bool;
}
