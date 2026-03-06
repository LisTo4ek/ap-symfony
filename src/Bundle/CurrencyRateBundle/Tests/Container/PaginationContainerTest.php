<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use PHPUnit\Framework\TestCase;

class PaginationContainerTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $c = new PaginationContainer();
        $this->assertSame(1, $c->page);
        $this->assertSame(0, $c->perPage);
    }
    public function testCustomValues(): void
    {
        $c = new PaginationContainer(page: 5, perPage: 50);
        $this->assertSame(5, $c->page);
        $this->assertSame(50, $c->perPage);
    }
    public function testPageCanBeOne(): void
    {
        $c = new PaginationContainer(page: 1, perPage: 10);
        $this->assertSame(1, $c->page);
    }
    public function testPerPageBoundary(): void
    {
        $c = new PaginationContainer(page: 1, perPage: 100);
        $this->assertSame(100, $c->perPage);
    }
    public function testPropertiesArePublic(): void
    {
        $c = new PaginationContainer(page: 3, perPage: 25);
        // Direct property access (DTO pattern)
        $c->page = 4;
        $c->perPage = 30;
        $this->assertSame(4, $c->page);
        $this->assertSame(30, $c->perPage);
    }
}
