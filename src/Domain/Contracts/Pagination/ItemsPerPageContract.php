<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Pagination;

/**
 * Contract for items per page configuration
 *
 * Allows different implementations for pagination size options
 */
interface ItemsPerPageContract
{
    /**
     * Get all available options as array
     *
     * @return array<int>
     */
    public function getOptions(): array;

    /**
     * Get the default option value
     */
    public function getDefault(): int;

    /**
     * Get the minimum allowed value
     */
    public function getMin(): int;

    /**
     * Get the maximum allowed value
     */
    public function getMax(): int;

    /**
     * Validate and return a valid items per page value
     *
     * @param mixed $value The value to validate
     * @return int A valid items per page value
     */
    public function validate(mixed $value): int;

    /**
     * Check if a value is a valid option
     */
    public function isValid(int $value): bool;

    public function init(mixed $perPage): static;

    public function getPerPage(): int;
}

