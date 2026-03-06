<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Exception;

use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateBundleException;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderConfigurationException;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderException;
use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateProviderInvalidRateDataException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionHierarchyTest extends TestCase
{
    // -- CurrencyRateBundleException (root) --

    public function testBundleExceptionDefaultStatusCode(): void
    {
        $e = new CurrencyRateBundleException('msg');

        $this->assertSame(500, $e->getStatusCode());
    }

    public function testBundleExceptionMessage(): void
    {
        $e = new CurrencyRateBundleException('something broke');

        $this->assertSame('something broke', $e->getMessage());
    }

    public function testBundleExceptionAcceptsCode(): void
    {
        $e = new CurrencyRateBundleException('msg', 42);

        $this->assertSame(42, $e->getCode());
    }

    public function testBundleExceptionChainsPrevious(): void
    {
        $prev = new RuntimeException('root cause');
        $e = new CurrencyRateBundleException('msg', 0, $prev);

        $this->assertSame($prev, $e->getPrevious());
    }

    public function testBundleExceptionWithEmptyMessage(): void
    {
        $e = new CurrencyRateBundleException();

        $this->assertSame('', $e->getMessage());
    }

    // -- CurrencyRateProviderException --

    public function testProviderExceptionExtendsBundleException(): void
    {
        $e = new CurrencyRateProviderException('fail');

        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testProviderExceptionStatusCode503(): void
    {
        $e = new CurrencyRateProviderException('fail');

        $this->assertSame(503, $e->getStatusCode());
    }

    // -- CurrencyRateProviderConfigurationException --

    public function testConfigurationExceptionExtendsProviderException(): void
    {
        $e = new CurrencyRateProviderConfigurationException('bad config');

        $this->assertInstanceOf(CurrencyRateProviderException::class, $e);
        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testConfigurationExceptionStatusCode500(): void
    {
        $e = new CurrencyRateProviderConfigurationException('bad config');

        $this->assertSame(500, $e->getStatusCode());
    }

    // -- CurrencyRateProviderInvalidRateDataException --

    public function testInvalidRateDataExceptionExtendsProviderException(): void
    {
        $e = new CurrencyRateProviderInvalidRateDataException('bad data');

        $this->assertInstanceOf(CurrencyRateProviderException::class, $e);
        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testInvalidRateDataExceptionStatusCode422(): void
    {
        $e = new CurrencyRateProviderInvalidRateDataException('bad data');

        $this->assertSame(422, $e->getStatusCode());
    }

    // -- catch hierarchy --

    public function testCatchProviderExceptionCatchesConfigurationException(): void
    {
        $caught = false;
        try {
            throw new CurrencyRateProviderConfigurationException('x');
        } catch (CurrencyRateProviderException) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testCatchBundleExceptionCatchesAllDescendants(): void
    {
        $exceptions = [
            new CurrencyRateProviderException('a'),
            new CurrencyRateProviderConfigurationException('b'),
            new CurrencyRateProviderInvalidRateDataException('c'),
        ];

        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            } catch (CurrencyRateBundleException) {
                $caught = true;
            }
            $this->assertTrue($caught, get_class($exception) . ' should be caught by CurrencyRateBundleException');
        }
    }
}
