<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationDoctrineService;
use App\Bundle\CurrencyRateBundle\Src\Service\PaginationPageableServiceInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class PaginationDoctrineServiceTest extends TestCase
{
    private PaginationDoctrineService $service;
    private PaginationConfigDefault $config;
    protected function setUp(): void
    {
        $this->service = new PaginationDoctrineService();
        $this->config = new PaginationConfigDefault();
    }
    public function testPaginateReturnsResult(): void
    {
        $pageable = $this->makePageable(items: [new stdClass()], totalCount: 1, totalPages: 1);
        $result = $this->service->paginate($pageable, $this->config, 10, 1);
        $this->assertInstanceOf(PaginationResultInterface::class, $result);
    }
    public function testResultReflectsPageAndPerPage(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 50, totalPages: 5);
        $result = $this->service->paginate($pageable, $this->config, 10, 3);
        $this->assertSame(3, $result->getCurrentPage());
        $this->assertSame(10, $result->getPerPage());
        $this->assertSame(50, $result->getTotalCount());
        $this->assertSame(5, $result->getTotalPages());
    }
    public function testPageLessThanOneIsClampedToOne(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 10, totalPages: 1);
        $result = $this->service->paginate($pageable, $this->config, 10, 0);
        $this->assertSame(1, $result->getCurrentPage());
    }
    public function testNegativePageIsClampedToOne(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 10, totalPages: 1);
        $result = $this->service->paginate($pageable, $this->config, 10, -5);
        $this->assertSame(1, $result->getCurrentPage());
    }
    public function testPageBeyondTotalPagesIsClampedToLast(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 30, totalPages: 3);
        $result = $this->service->paginate($pageable, $this->config, 10, 99);
        $this->assertSame(3, $result->getCurrentPage());
    }
    public function testPageBeyondTotalPagesWithZeroTotalKeepsRequestedPage(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 0, totalPages: 0);
        $result = $this->service->paginate($pageable, $this->config, 10, 5);
        // totalPages is 0, so the "if $page > $totalPages && $totalPages > 0" guard does NOT fire
        $this->assertSame(5, $result->getCurrentPage());
    }
    public function testEmptyDataset(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 0, totalPages: 0);
        $result = $this->service->paginate($pageable, $this->config, 10, 1);
        $this->assertSame(0, $result->getTotalCount());
        $this->assertSame([], $result->getItems());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
    }
    public function testConfigPassedThrough(): void
    {
        $pageable = $this->makePageable(items: [], totalCount: 0, totalPages: 0);
        $result = $this->service->paginate($pageable, $this->config, 10, 1);
        $this->assertSame($this->config, $result->getConfig());
    }
    /**
     * @param array<object> $items
     */
    private function makePageable(array $items, int $totalCount, int $totalPages): PaginationPageableServiceInterface
    {
        $pageable = $this->createMock(PaginationPageableServiceInterface::class);
        $pageable->method('getTotalCount')->willReturn($totalCount);
        $pageable->method('getTotalPages')->willReturn($totalPages);
        $pageable->method('getPage')->willReturn($items);
        return $pageable;
    }
}
