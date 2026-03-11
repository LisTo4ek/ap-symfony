<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;

/**
 * Simple implementation of PaginationResult
 *
 * @template T of object
 * @implements PaginationResultInterface<T>
 *
 * @property int $currentPage The current page number (1-indexed)
 * @property int $perPage Number of items per page
 * @property int $totalCount Total number of items across all pages
 * @property PaginationConfigInterface $config Pagination configuration (allowed per-page options, etc.)
 * @property int $totalPages Total number of pages
 * @property array<T> $items Items for the current page
 */
class PaginationResultContainer implements PaginationResultInterface
{
    public function __construct(
        private readonly int $currentPage,
        private readonly int $perPage,
        private readonly int $totalCount,
        private readonly PaginationConfigInterface $config,
        private readonly int $totalPages,
        /** @var array<T> */
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
