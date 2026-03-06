<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationResultInterface;
use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use DateTimeImmutable;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use stdClass;

class PaginationResultContainerTest extends TestCase
{
    private PaginationConfigDefault $config;
    protected function setUp(): void
    {
        $this->config = new PaginationConfigDefault();
    }
    public function testImplementsInterface(): void
    {
        $result = $this->make([], 0, 0);
        $this->assertInstanceOf(PaginationResultInterface::class, $result);
    }
    public function testGettersReturnConstructorValues(): void
    {
        $result = $this->make([], 50, 5, page: 2, perPage: 10);
        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(10, $result->getPerPage());
        $this->assertSame(50, $result->getTotalCount());
        $this->assertSame(5, $result->getTotalPages());
        $this->assertSame($this->config, $result->getConfig());
        $this->assertSame([], $result->getItems());
    }
    public function testHasNextPageTrueWhenNotOnLastPage(): void
    {
        $result = $this->make([], 30, 3, page: 1, perPage: 10);
        $this->assertTrue($result->hasNextPage());
    }
    public function testHasNextPageFalseOnLastPage(): void
    {
        $result = $this->make([], 30, 3, page: 3, perPage: 10);
        $this->assertFalse($result->hasNextPage());
    }
    public function testHasNextPageFalseForSinglePage(): void
    {
        $result = $this->make([], 5, 1, page: 1, perPage: 10);
        $this->assertFalse($result->hasNextPage());
    }
    public function testHasPreviousPageFalseOnFirstPage(): void
    {
        $result = $this->make([], 30, 3, page: 1, perPage: 10);
        $this->assertFalse($result->hasPreviousPage());
    }
    public function testHasPreviousPageTrueAfterFirstPage(): void
    {
        $result = $this->make([], 30, 3, page: 2, perPage: 10);
        $this->assertTrue($result->hasPreviousPage());
    }
    public function testItemsReturnedAsIs(): void
    {
        $items = [
            new CurrentRate(new Currency('RUB'), new Currency('USD'), '75', new DateTimeImmutable()),
            new CurrentRate(new Currency('RUB'), new Currency('EUR'), '85', new DateTimeImmutable()),
        ];
        $result = $this->make($items, 2, 1);
        $this->assertCount(2, $result->getItems());
        $this->assertSame($items, $result->getItems());
    }
    public function testEmptyResultSet(): void
    {
        $result = $this->make([], 0, 0, page: 1, perPage: 10);
        $this->assertSame(0, $result->getTotalCount());
        $this->assertSame(0, $result->getTotalPages());
        $this->assertSame([], $result->getItems());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
    }
    /**
     * @param array<object> $items
     */
    private function make(
        array $items,
        int $totalCount,
        int $totalPages,
        int $page = 1,
        int $perPage = 10,
    ): PaginationResultContainer {
        return new PaginationResultContainer(
            currentPage: $page,
            perPage: $perPage,
            totalCount: $totalCount,
            config: $this->config,
            totalPages: $totalPages,
            items: $items,
        );
    }
}
