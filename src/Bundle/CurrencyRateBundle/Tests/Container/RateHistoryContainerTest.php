<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use PHPUnit\Framework\TestCase;

class RateHistoryContainerTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $pagination = new PaginationContainer(page: 3, perPage: 50);
        $dto = new RateHistoryContainer(
            pagination: $pagination,
            baseCurrencyCode: 'RUB',
            targetCurrencyCode: 'EUR',
        );
        $this->assertSame('RUB', $dto->baseCurrencyCode);
        $this->assertSame('EUR', $dto->targetCurrencyCode);
        $this->assertSame(3, $dto->pagination->page);
    }

    public function testDefaultBaseCurrencyIsRUB(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(),
        );
        $this->assertSame(CurrencyEnum::RUB->value, $dto->baseCurrencyCode);
    }

    public function testDefaultTargetCurrencyIsEmpty(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(),
        );
        $this->assertSame('', $dto->targetCurrencyCode);
    }
}
