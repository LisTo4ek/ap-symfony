<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Entity;

use App\Bundle\CurrencyRateBundle\Src\Entity\MoneyCurrencyType;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class MoneyCurrencyTypeTest extends TestCase
{
    use CurrencyTrait;

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
        $result = $this->type->convertToPHPValue(self::getUsd()->getCode(), $this->platform);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertSame(self::getUsd()->getCode(), $result->getCode());
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
        $result = $this->type->convertToDatabaseValue(self::getEur(), $this->platform);

        $this->assertSame(self::getEur()->getCode(), $result);
    }

    public function testConvertToDatabaseValuePassesThroughString(): void
    {
        $result = $this->type->convertToDatabaseValue(self::getGbp()->getCode(), $this->platform);

        $this->assertSame(self::getGbp()->getCode(), $result);
    }

    public function testConvertToDatabaseValueThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue(123, $this->platform);
    }

    public function testRoundTripConversion(): void
    {
        $original = self::getJpy();
        $dbValue = $this->type->convertToDatabaseValue($original, $this->platform);
        $phpValue = $this->type->convertToPHPValue($dbValue, $this->platform);

        $this->assertInstanceOf(Currency::class, $phpValue);
        $this->assertSame($original->getCode(), $phpValue->getCode());
    }
}
