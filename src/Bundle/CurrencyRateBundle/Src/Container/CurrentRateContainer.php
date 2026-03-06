<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Validator\Constraints as Assert;

class CurrentRateContainer
{
    public function __construct(
        #[Assert\Valid]
        #[MapQueryString]
        public PaginationContainer $pagination,
        #[Assert\NotBlank(message: 'Base currency code is required')]
        #[Assert\Currency]
        public string $baseCurrencyCode = CurrencyEnum::RUB->value,
    ) {
    }
}
