<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Config;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use PHPUnit\Framework\TestCase;

class PaginationConfigDefaultTest extends TestCase
{
    private PaginationConfigDefault $config;

    protected function setUp(): void
    {
        $this->config = new PaginationConfigDefault();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(PaginationConfigInterface::class, $this->config);
    }

    public function testGetPerPageOptionsReturnsNonEmptyArray(): void
    {
        $this->assertNotEmpty($this->config->getPerPageOptions());
        $this->assertContains(10, $this->config->getPerPageOptions());
    }

    public function testGetPerPageDefaultIsInOptions(): void
    {
        $this->assertContains(
            $this->config->getPerPageDefault(),
            $this->config->getPerPageOptions()
        );
    }

    public function testGetPerPageMinIsPositive(): void
    {
        $this->assertGreaterThan(0, $this->config->getPerPageMin());
    }

    public function testGetPerPageMaxIsGreaterThanMin(): void
    {
        $this->assertGreaterThan($this->config->getPerPageMin(), $this->config->getPerPageMax());
    }

    public function testValidatePerPageAcceptsValidOption(): void
    {
        $this->assertSame(10, $this->config->validatePerPage(10));
    }

    public function testValidatePerPageFallsBackToDefaultForUnknownInt(): void
    {
        $this->assertSame($this->config->getPerPageDefault(), $this->config->validatePerPage(7));
    }

    public function testValidatePerPageFallsBackForNonInt(): void
    {
        $default = $this->config->getPerPageDefault();
        $this->assertSame($default, $this->config->validatePerPage('abc'));
        $this->assertSame($default, $this->config->validatePerPage(null));
        $this->assertSame($default, $this->config->validatePerPage(3.14));
    }

    public function testValidatePerPageFallsBackForNegative(): void
    {
        $this->assertSame($this->config->getPerPageDefault(), $this->config->validatePerPage(-1));
    }

    public function testIsValidPerPageReturnsTrueForKnownValues(): void
    {
        foreach ($this->config->getPerPageOptions() as $opt) {
            $this->assertTrue($this->config->isValidPerPage($opt), "Expected $opt to be valid");
        }
    }

    public function testIsValidPerPageReturnsFalseForUnknownValues(): void
    {
        $this->assertFalse($this->config->isValidPerPage(0));
        $this->assertFalse($this->config->isValidPerPage(7));
        $this->assertFalse($this->config->isValidPerPage(999));
    }

    public function testAllOptionsWithinMinMax(): void
    {
        $min = $this->config->getPerPageMin();
        $max = $this->config->getPerPageMax();
        foreach ($this->config->getPerPageOptions() as $opt) {
            $this->assertGreaterThanOrEqual($min, $opt);
            $this->assertLessThanOrEqual($max, $opt);
        }
    }
}
