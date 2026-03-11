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
     * @inheritDoc
     */
    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    /**
     * @inheritDoc
     */
    public function getPerPageDefault(): int
    {
        return $this->perPageDefault;
    }

    /**
     * @inheritDoc
     */
    public function getPerPageMin(): int
    {
        return $this->perPageMin;
    }

    /**
     * @inheritDoc
     */
    public function getPerPageMax(): int
    {
        return $this->perPageMax;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function isValidPerPage(int $perPage): bool
    {
        return in_array($perPage, $this->getPerPageOptions(), true);
    }
}
