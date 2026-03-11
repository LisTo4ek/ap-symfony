<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for the current-rates endpoint.
 *
 * Holds validated pagination parameters and the base currency code
 * extracted from the HTTP request by CurrentRateContainerResolver.
 */
class CurrentRateContainer
{
    /**
     * @param PaginationContainer $pagination Validated pagination parameters (page, perPage)
     * @param string $baseCurrencyCode ISO 4217 base currency code (defaults to RUB)
     */
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
