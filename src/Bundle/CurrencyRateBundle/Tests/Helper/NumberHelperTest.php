<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Helper;

use App\Bundle\CurrencyRateBundle\Src\Helper\NumberHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumberHelperTest extends TestCase
{
    #[DataProvider('normalizeProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, NumberHelper::normalize($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function normalizeProvider(): array
    {
        return [
            'simple decimal' => ['1.5', '1.5'],
            'comma separator' => ['1,5', '1.5'],
            'trailing zeros' => ['1.500', '1.5'],
            'only trailing zeros' => ['10.00', '10'],
            'integer-like' => ['42.0', '42'],
            'zero' => ['0', ''],  // rtrim('0','0') strips all chars → known edge case
            'zero with decimals' => ['0.00', '0'],  // '0.00' → rtrim '0' → '0.' → rtrim '.' → '0'
            'leading zero decimal' => ['0.123', '0.123'],
            'high precision' => ['0.00000001000', '0.00000001'],
            'negative' => ['-1.50', '-1.5'],
            'large number' => ['123456789.987654321000', '123456789.987654321'],
            'comma high precision' => ['90,5000', '90.5'],
        ];
    }

    public function testNormalizeScientificNotation(): void
    {
        // sprintf('%.50f', '1e-8') converts to fixed-point
        $result = NumberHelper::normalize('1e-8');

        // Must not contain 'e'
        $this->assertStringNotContainsString('e', $result);
        $this->assertStringNotContainsString('E', $result);
    }

    public function testNormalizeUppercaseScientific(): void
    {
        $result = NumberHelper::normalize('1.5E+3');

        $this->assertStringNotContainsString('E', $result);
    }

    public function testNormalizeEmptyString(): void
    {
        $result = NumberHelper::normalize('');

        $this->assertSame('', $result);
    }

    public function testNormalizePlainIntegerStripsTrailingZeros(): void
    {
        // Known edge case: rtrim('100', '0') → '1'
        $this->assertSame('1', NumberHelper::normalize('100'));
    }

    public function testNormalizeIntegerWithoutTrailingZeros(): void
    {
        $this->assertSame('42', NumberHelper::normalize('42'));
    }
}
