<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Config;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

use function in_array;

/**
 * Default implementation of items per page configuration
 *
 * Provides centralized configuration and validation for pagination
 * Can be extended to provide custom options
 */
#[AsAlias(PaginationConfigInterface::class)]
class PaginationConfigDefault implements PaginationConfigInterface
{
    /**
     * @var array<int>
     */
    protected array $perPageOptions = [1, 5, 10, 25, 50, 100];

    protected int $perPageDefault = 10;

    protected int $perPageMin = 1;

    protected int $perPageMax = 100;

    /**
     * Get all available options as array
     *
     * @return array<int>
     */
    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    /**
     * Get the default option value
     */
    public function getPerPageDefault(): int
    {
        return $this->perPageDefault;
    }

    /**
     * Get the minimum allowed value
     */
    public function getPerPageMin(): int
    {
        return $this->perPageMin;
    }

    /**
     * Get the maximum allowed value
     */
    public function getPerPageMax(): int
    {
        return $this->perPageMax;
    }

    /**
     * Validate and return a valid items per page value
     *
     * @param mixed $perPage The value to validate
     * @return int A valid items per page value
     */
    public function validatePerPage(mixed $perPage): int
    {
        if (!is_int($perPage)) {
            return $this->getPerPageDefault();
        }

        if (in_array($perPage, $this->getPerPageOptions(), true)) {
            return $perPage;
        }

        return $this->getPerPageDefault();
    }

    /**
     * Check if a value is a valid option
     */
    public function isValidPerPage(int $perPage): bool
    {
        return in_array($perPage, $this->getPerPageOptions(), true);
    }
}
