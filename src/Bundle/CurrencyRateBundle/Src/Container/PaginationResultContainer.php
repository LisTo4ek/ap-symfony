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
     * @param int $currentPage The current page number (1-indexed)
     * @param int $perPage Number of items per page
     * @param int $totalCount Total number of items across all pages
     * @param PaginationConfigInterface $config Pagination configuration (allowed per-page options, etc.)
     * @param int $totalPages Total number of pages
     * @param array<T> $items Items for the current page
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
     * @inheritDoc
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @inheritDoc
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): PaginationConfigInterface
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * @inheritDoc
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}
