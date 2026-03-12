<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Service\BundleLoggerService;
use App\Bundle\CurrencyRateBundle\Src\Service\ProviderLoggerService;
use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CurrencyRateProviderLoggerServiceTest extends TestCase
{
    use CurrencyTrait;

    private LoggerInterface&MockObject $psr3Logger;
    private BundleLoggerService $logger;

    protected function setUp(): void
    {
        $this->psr3Logger = $this->createMock(LoggerInterface::class);
        $this->logger = new BundleLoggerService($this->psr3Logger);
    }

    /**
     * Test PSR-3 proxy methods
     */
    public function testPsr3ProxyMethods(): void
    {
        $this->psr3Logger
            ->expects($this->once())
            ->method('debug')
            ->with('Debug message', ['key' => 'value']);
        $this->logger->debug('Debug message', ['key' => 'value']);
        $this->psr3Logger
            ->expects($this->once())
            ->method('info')
            ->with('Info message', []);
        $this->logger->info('Info message');
        $this->psr3Logger
            ->expects($this->once())
            ->method('warning')
            ->with('Warning message', []);
        $this->logger->warning('Warning message');
        $this->psr3Logger
            ->expects($this->once())
            ->method('error')
            ->with('Error message', []);
        $this->logger->error('Error message');
    }

    /**
     * Test getLogger returns underlying logger
     */
    public function testGetLogger(): void
    {
        $result = $this->logger->getLogger();
        $this->assertSame($this->psr3Logger, $result);
    }

    /**
     * Test contextual data is passed correctly
     */
    public function testContextualDataPassedCorrectly(): void
    {
        $context = [
            'duration_ms' => 245,
            'content_size' => 5000,
            'status_code' => 200,
        ];
        $this->psr3Logger
            ->expects($this->once())
            ->method('debug')
            ->with('Test message', $context);
        $this->logger->debug('Test message', $context);
    }
}
