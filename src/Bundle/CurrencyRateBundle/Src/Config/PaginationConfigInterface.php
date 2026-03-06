<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Config;

/**
 * Contract for items per page configuration
 *
 * Allows different implementations for pagination size options
 */
interface PaginationConfigInterface
{
    /**
     * Get all available options as array
     *
     * @return array<int>
     */
    public function getPerPageOptions(): array;

    /**
     * Get the default option value
     */
    public function getPerPageDefault(): int;

    /**
     * Get the minimum allowed value
     */
    public function getPerPageMin(): int;

    /**
     * Get the maximum allowed value
     */
    public function getPerPageMax(): int;

    /**
     * Validate and return a valid items per page value
     *
     * @param mixed $perPage The value to validate
     * @return int A valid items per page value
     */
    public function validatePerPage(mixed $perPage): int;

    /**
     * Check if a value is a valid option
     */
    public function isValidPerPage(int $perPage): bool;
}
