<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\MoneyCurrencyType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class MoneyCurrencyTypeTest extends TestCase
{
    private MoneyCurrencyType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new MoneyCurrencyType();
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    public function testGetNameReturnsConstant(): void
    {
        $this->assertSame('money_currency', $this->type->getName());
        $this->assertSame(MoneyCurrencyType::NAME, $this->type->getName());
    }

    public function testConvertToPHPValueReturnsNullForNull(): void
    {
        $result = $this->type->convertToPHPValue(null, $this->platform);

        $this->assertNull($result);
    }

    public function testConvertToPHPValueReturnsCurrencyForString(): void
    {
        $result = $this->type->convertToPHPValue('USD', $this->platform);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertSame('USD', $result->getCode());
    }

    public function testConvertToPHPValueThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToPHPValue('', $this->platform);
    }

    public function testConvertToPHPValueThrowsOnInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToPHPValue(123, $this->platform);
    }

    public function testConvertToDatabaseValueReturnsNullForNull(): void
    {
        $result = $this->type->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($result);
    }

    public function testConvertToDatabaseValueReturnsCodeForCurrency(): void
    {
        $result = $this->type->convertToDatabaseValue(new Currency('EUR'), $this->platform);

        $this->assertSame('EUR', $result);
    }

    public function testConvertToDatabaseValuePassesThroughString(): void
    {
        $result = $this->type->convertToDatabaseValue('GBP', $this->platform);

        $this->assertSame('GBP', $result);
    }

    public function testConvertToDatabaseValueThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue(123, $this->platform);
    }

    public function testRoundTripConversion(): void
    {
        $original = new Currency('JPY');
        $dbValue = $this->type->convertToDatabaseValue($original, $this->platform);
        $phpValue = $this->type->convertToPHPValue($dbValue, $this->platform);

        $this->assertInstanceOf(Currency::class, $phpValue);
        $this->assertSame('JPY', $phpValue->getCode());
    }
}
