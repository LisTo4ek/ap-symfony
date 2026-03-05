<?php

declare(strict_types=1);

namespace App\Domain\Dto\CurrencyRate;

use App\Domain\Enum\CurrencyEnum;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Validator\Constraints as Assert;

class RateHistoryDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Base currency code is required')]
        #[Assert\Length(exactly: 3, exactMessage: 'Currency code must be exactly 3 characters')]
        #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Currency code must be uppercase letters only')]
//        #[Assert\Choice(
//            choices: CurrencyEnum::class,
//            message: 'Choose a valid status.'
//        )]
        public string $baseCurrencyCode = CurrencyEnum::RUB->value,

        #[Assert\NotBlank(message: 'Target currency code is required')]
        #[Assert\Length(exactly: 3, exactMessage: 'Currency code must be exactly 3 characters')]
        #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Currency code must be uppercase letters only')]
        public string $targetCurrencyCode = '',

        #[Assert\Valid]
        #[MapQueryString]
        public PaginationDto $pagination,
    ) {
    }
}
