<?php

declare(strict_types=1);

namespace App\Domain\Pagination;

use App\Domain\Contracts\Pagination\ItemsPerPageContract;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Default implementation of items per page configuration
 *
 * Provides centralized configuration and validation for pagination
 * Can be extended to provide custom options
 */
#[AsAlias(ItemsPerPageContract::class)]
class ItemsPerPageDefault implements ItemsPerPageContract
{
    /**
     * @var array<int>
     */
    protected array $options = [1, 5, 10, 25, 50, 100];

    protected int $default = 10;

    protected int $min = 1;

    protected int $max = 100;

    protected int $perPage = 0;

    /**
     * Get all available options as array
     *
     * @return array<int>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the default option value
     */
    public function getDefault(): int
    {
        return $this->default;
    }

    /**
     * Get the minimum allowed value
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Get the maximum allowed value
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * Validate and return a valid items per page value
     *
     * @param mixed $value The value to validate
     * @return int A valid items per page value
     */
    public function validate(mixed $value): int
    {
        if (!is_int($value)) {
            return $this->getDefault();
        }

        if (in_array($value, $this->getOptions(), true)) {
            return $value;
        }

        return $this->getDefault();
    }

    /**
     * Check if a value is a valid option
     */
    public function isValid(int $value): bool
    {
        return in_array($value, $this->getOptions(), true);
    }

    public function init(mixed $perPage): static
    {
        $this->perPage = $this->validate($perPage);

        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}

