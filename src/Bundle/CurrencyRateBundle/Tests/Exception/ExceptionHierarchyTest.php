<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Exception;

use App\Bundle\CurrencyRateBundle\Src\Exception\CurrencyRateBundleException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderConfigurationException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderException;
use App\Bundle\CurrencyRateBundle\Src\Exception\InvalidRateDataException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionHierarchyTest extends TestCase
{
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

    public function testProviderExceptionExtendsBundleException(): void
    {
        $e = new ProviderException('fail');

        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testProviderExceptionStatusCode503(): void
    {
        $e = new ProviderException('fail');

        $this->assertSame(503, $e->getStatusCode());
    }

    public function testConfigurationExceptionExtendsProviderException(): void
    {
        $e = new ProviderConfigurationException('bad config');

        $this->assertInstanceOf(ProviderException::class, $e);
        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testConfigurationExceptionStatusCode500(): void
    {
        $e = new ProviderConfigurationException('bad config');

        $this->assertSame(500, $e->getStatusCode());
    }

    public function testInvalidRateDataExceptionExtendsProviderException(): void
    {
        $e = new InvalidRateDataException('bad data');

        $this->assertInstanceOf(ProviderException::class, $e);
        $this->assertInstanceOf(CurrencyRateBundleException::class, $e);
    }

    public function testInvalidRateDataExceptionStatusCode422(): void
    {
        $e = new InvalidRateDataException('bad data');

        $this->assertSame(422, $e->getStatusCode());
    }

    public function testCatchProviderExceptionCatchesConfigurationException(): void
    {
        $caught = false;
        try {
            throw new ProviderConfigurationException('x');
        } catch (ProviderException) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testCatchBundleExceptionCatchesAllDescendants(): void
    {
        $exceptions = [
            new ProviderException('a'),
            new ProviderConfigurationException('b'),
            new InvalidRateDataException('c'),
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
