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
     */
    public function getCurrentPage(): int;

    /**
     * Get items per page count
     */
    public function getPerPage(): int;

    /**
     * Get total number of items
     */
    public function getTotalCount(): int;

    /**
     * Get items per page
     */
    public function getConfig(): PaginationConfigInterface;

    /**
     * Get total number of pages
     */
    public function getTotalPages(): int;

    /**
     * Get items for current page
     *
     * @return array<T>
     */
    public function getItems(): array;

    /**
     * Check if there is a next page
     */
    public function hasNextPage(): bool;

    /**
     * Check if there is a previous page
     */
    public function hasPreviousPage(): bool;
}
