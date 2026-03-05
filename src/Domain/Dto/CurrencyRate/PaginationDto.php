<?php

declare(strict_types=1);

namespace App\Domain\Dto\CurrencyRate;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationDto
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
