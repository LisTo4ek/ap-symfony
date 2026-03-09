<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use PHPUnit\Framework\TestCase;

class CurrentRateContainerTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $pagination = new PaginationContainer(page: 2, perPage: 25);
        $dto = new CurrentRateContainer(
            pagination: $pagination,
            baseCurrencyCode: 'USD',
        );
        $this->assertSame('USD', $dto->baseCurrencyCode);
        $this->assertSame(2, $dto->pagination->page);
        $this->assertSame(25, $dto->pagination->perPage);
    }

    public function testDefaultBaseCurrencyIsRUB(): void
    {
        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(),
        );
        $this->assertSame(CurrencyEnum::RUB->value, $dto->baseCurrencyCode);
    }

    public function testPaginationObjectIsShared(): void
    {
        $pagination = new PaginationContainer(page: 1, perPage: 10);
        $dto = new CurrentRateContainer(pagination: $pagination);
        $this->assertSame($pagination, $dto->pagination);
    }
}
