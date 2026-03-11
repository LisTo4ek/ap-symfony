<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO that holds pagination request parameters.
 *
 * Validated via Symfony constraints to ensure page ≥ 1 and perPage within 1–100.
 *
 * @property int $page The requested page number (must be ≥ 1)
 * @property int $perPage The number of items per page (must be between 1 and 100)
 */
class PaginationContainer
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('integer', 'Page must be an integer')]
        #[Assert\GreaterThanOrEqual(1, message: 'Page must be at least 1')]
        public int $page = 1,
        #[Assert\NotBlank]
        #[Assert\GreaterThanOrEqual(1, message: 'Items per page must be at least 1')]
        #[Assert\LessThanOrEqual(100, message: 'Items per page cannot exceed 100')]
        public int $perPage = 0,
    ) {
    }
}
