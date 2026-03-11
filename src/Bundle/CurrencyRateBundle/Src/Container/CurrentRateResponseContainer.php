<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Container;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use DateTimeImmutable;
use Throwable;

/**
 * Immutable response container returned by CurrentRatesGetterService.
 *
 * Holds the latest stored rate date, an optional paginated result set of current rates,
 * and any exception thrown during the CBR import attempt.
 */
class CurrentRateResponseContainer
{
    /**
     * @param DateTimeImmutable|null $latestDate The most recent rate date found in storage, or null if no rates exist
     * @param Throwable|null $importRatesException Exception caught during CBR import, or null on success / when import
     *        was skipped
     * @param PaginationResultInterface<CurrentRate>|null $pagination current rates for the requested base currency,
     *        or null when the currency code is empty or no rates are available
     */
    public function __construct(
        public readonly ?DateTimeImmutable $latestDate = null,
        public readonly ?Throwable $importRatesException = null,
        public readonly ?PaginationResultInterface $pagination = null,
    ) {
    }
}
