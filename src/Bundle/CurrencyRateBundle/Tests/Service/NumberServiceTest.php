<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Service\NumberService;
use Brick\Math\Exception\NumberFormatException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumberServiceTest extends TestCase
{
    #[DataProvider('normalizeProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, NumberService::normalize($input)->toString());
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function normalizeProvider(): Generator
    {
        yield 'simple decimal' => ['1.5', '1.5'];
        yield 'comma separator' => ['1,5', '1.5'];
        yield 'trailing zeros' => ['1.500', '1.5'];
        yield 'only trailing zeros' => ['10.00', '10'];
        yield 'integer-like' => ['42.0', '42'];
        yield 'zero with decimals' => ['0.00', '0'];  // '0.00' → rtrim '0' → '0.' → rtrim '.' → '0'
        yield 'leading zero decimal' => ['0.123', '0.123'];
        yield 'high precision' => ['0.00000001000', '0.00000001'];
        yield 'negative' => ['-1.50', '-1.5'];
        yield 'large number' => ['123456789.987654321000', '123456789.987654321'];
        yield 'comma high precision' => ['90,5000', '90.5'];
        yield 'plain integer doesn\'t strip trailing zeros' => ['100', '100'];
        yield 'plain integer without trailing zeros' => ['42', '42'];
    }

    public function testNormalizeScientificNotation(): void
    {
        $result = NumberService::normalize('14839200e-40');
        $this->assertStringNotContainsString('e', $result->toString());
        $this->assertStringNotContainsString('E', $result->toString());
    }

    public function testNormalizeUppercaseScientific(): void
    {
        $result = NumberService::normalize('1.5E+3');
        $this->assertStringNotContainsString('e', $result->toString());
        $this->assertStringNotContainsString('E', $result->toString());
    }

    public function testNormalizeEmptyString(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage('Value "" does not represent a valid number');
        NumberService::normalize('');
    }
}
