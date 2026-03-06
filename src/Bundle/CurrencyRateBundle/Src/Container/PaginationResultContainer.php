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
     * @param array<T> $items
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

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getConfig(): PaginationConfigInterface
    {
        return $this->config;
    }

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

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}
