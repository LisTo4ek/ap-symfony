<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Container;

use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use PHPUnit\Framework\TestCase;

class RateHistoryContainerTest extends TestCase
{
    use CurrencyTrait;

    public function testConstructorSetsAllProperties(): void
    {
        $pagination = new PaginationContainer(page: 3, perPage: 50);
        $dto = new RateHistoryContainer(
            pagination: $pagination,
            baseCurrencyCode: self::getRub()->getCode(),
            targetCurrencyCode: self::getEur()->getCode(),
        );
        $this->assertSame(self::getRub()->getCode(), $dto->baseCurrencyCode);
        $this->assertSame(self::getEur()->getCode(), $dto->targetCurrencyCode);
        $this->assertSame(3, $dto->pagination->page);
    }

    public function testDefaultBaseCurrencyIsRUB(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(),
        );
        $this->assertSame(self::getRub()->getCode(), $dto->baseCurrencyCode);
    }

    public function testDefaultTargetCurrencyIsEmpty(): void
    {
        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(),
        );
        $this->assertSame('', $dto->targetCurrencyCode);
    }
}
