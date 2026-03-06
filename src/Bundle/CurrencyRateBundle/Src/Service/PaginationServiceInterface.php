<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;

/**
 * Paginator abstraction that doesn't depend on specific libraries or HTTP requests
 *
 * This interface allows pagination to be decoupled from KnpPaginator and QueryBuilder
 * Works with PageableContract to fetch pages efficiently from any data source
 *
 * @template T of object
 */
interface PaginationServiceInterface
{
    /**
     * Paginate a query
     *
     * @template TItem of object
     * @param PaginationPageableServiceInterface<TItem> $pageable Query abstraction that provides pagination support
     * @param PaginationConfigInterface $config Number of items per page configuration
     * @param int $perPage Number of items per page
     * @param int $page Current page (1-indexed)
     * @return PaginationResultInterface<TItem>
     */
    public function paginate(
        PaginationPageableServiceInterface $pageable,
        PaginationConfigInterface $config,
        int $perPage,
        int $page = 1
    ): PaginationResultInterface;
}
