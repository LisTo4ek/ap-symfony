<?php

declare(strict_types=1);

namespace App\Domain\Dto\CurrencyRate;

use App\Domain\Enum\CurrencyEnum;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Validator\Constraints as Assert;

class CurrentRateDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Base currency code is required')]
        #[Assert\Length(exactly: 3, exactMessage: 'Currency code must be exactly 3 characters')]
        #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Currency code must be uppercase letters only')]
        public string $baseCurrencyCode = CurrencyEnum::RUB->value,

        #[Assert\Valid]
        #[MapQueryString]
        public PaginationDto $pagination,
    ) {
    }
}
