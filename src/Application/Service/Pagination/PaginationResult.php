<?php

declare(strict_types=1);

namespace App\Application\Service\Pagination;

use App\Domain\Contracts\Pagination\PaginationResultContract;

/**
 * Simple implementation of PaginationResult
 *
 * @template T of object
 * @implements PaginationResultContract<T>
 */
class PaginationResult implements PaginationResultContract
{
    /**
     * @param int $currentPage
     * @param int $totalCount
     * @param int $itemsPerPage
     * @param int $totalPages
     * @param array<T> $items
     */
    public function __construct(
        private readonly int $currentPage,
        private readonly int $totalCount,
        private readonly int $itemsPerPage,
        private readonly int $totalPages,
        private readonly array $items,
    ) {
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
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


