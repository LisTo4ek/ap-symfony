<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for the rate-history endpoint.
 *
 * Holds validated pagination parameters and the currency pair codes
 * extracted from the HTTP request by RateHistoryContainerResolver.
 */
class RateHistoryContainer
{
    /**
     * @param PaginationContainer $pagination         Validated pagination parameters (page, perPage)
     * @param string              $baseCurrencyCode   ISO 4217 base currency code (defaults to RUB)
     * @param string              $targetCurrencyCode ISO 4217 target currency code
     */
    public function __construct(
        #[Assert\Valid]
        #[MapQueryString]
        public PaginationContainer $pagination,
        #[Assert\NotBlank(message: 'Base currency code is required')]
        #[Assert\Currency]
        public string $baseCurrencyCode = CurrencyEnum::RUB->value,
        #[Assert\NotBlank(message: 'Target currency code is required')]
        #[Assert\Currency]
        public string $targetCurrencyCode = '',
    ) {
    }
}
