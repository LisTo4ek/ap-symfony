<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderLoggerService;
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
    private CurrencyRateProviderLoggerService $logger;

    protected function setUp(): void
    {
        $this->psr3Logger = $this->createMock(LoggerInterface::class);
        $this->logger = new CurrencyRateProviderLoggerService($this->psr3Logger);
    }

    /**
     * Test logRateRetrieval calls underlying logger
     */
    public function testLogRateRetrieval(): void
    {
        $context = [
            'from' => self::getRub()->getCode(),
            'to' => self::getUsd()->getCode(),
            'rate' => '90.5',
            'duration_ms' => 100,
        ];

        $this->psr3Logger
            ->expects($this->once())
            ->method('info')
            ->with('Exchange rate retrieved', $context);

        $this->logger->logRateRetrieval($context['from'], $context['to'], $context['rate'], $context['duration_ms']);
    }

    /**
     * Test logProviderInit calls underlying logger
     */
    public function testLogProviderInit(): void
    {
        $config = ['api_url' => 'https://cbr.ru', 'timeout' => 30];

        $this->psr3Logger
            ->expects($this->once())
            ->method('info')
            ->with('Provider initialized', [
                'provider' => 'CBR',
                'config_keys' => ['api_url', 'timeout'],
            ]);

        $this->logger->logProviderInit('CBR', $config);
    }

    /**
     * Test logRateUpdate calls underlying logger
     */
    public function testLogRateUpdate(): void
    {
        $date = new DateTime('2026-03-02 15:30:00');
        $context = [
            'currency' => self::getUsd()->getCode(),
            'rate' => '90.5',
            'timestamp' => $date->format('Y-m-d H:i:s'),
        ];


        $this->psr3Logger
            ->expects($this->once())
            ->method('info')
            ->with('Currency rate updated', [
                'currency' => self::getUsd()->getCode(),
                'rate' => '90.5',
                'timestamp' => $date->format('Y-m-d H:i:s'),
            ]);

        $this->logger->logRateUpdate($context['currency'], $context['rate'], $date);
    }

    /**
     * Test logProviderError calls underlying logger
     */
    public function testLogProviderError(): void
    {
        $exception = new RuntimeException('Test error');

        $this->psr3Logger
            ->expects($this->once())
            ->method('error')
            ->with('Provider error', [
                'provider' => 'CBR',
                'error_message' => 'Failed to fetch rates',
                'exception' => 'Test error',
                'exception_code' => 0,
            ]);

        $this->logger->logProviderError('CBR', 'Failed to fetch rates', $exception);
    }

    /**
     * Test logRetrievalFailure calls underlying logger
     */
    public function testLogRetrievalFailure(): void
    {
        $this->psr3Logger
            ->expects($this->once())
            ->method('warning')
            ->with('Rate retrieval failed', [
                'from' => self::getRub()->getCode(),
                'to' => self::getUsd()->getCode(),
                'reason' => 'Connection timeout',
                'attempt' => 2,
            ]);

        $this->logger->logRetrievalFailure(
            self::getRub()->getCode(),
            self::getUsd()->getCode(),
            'Connection timeout',
            2
        );
    }

    /**
     * Test logValidationError calls underlying logger
     */
    public function testLogValidationError(): void
    {
        $errors = [
            self::getUsd()->getCode() => 'Invalid currency code',
            self::getEur()->getCode() => 'Not in monitored list'
        ];

        $this->psr3Logger
            ->expects($this->once())
            ->method('warning')
            ->with('Validation failed', [
                'errors' => $errors,
                'error_count' => 2,
            ]);

        $this->logger->logValidationError($errors);
    }

    /**
     * Test PSR-3 proxy methods
     */
    public function testPsr3ProxyMethods(): void
    {
        // Test debug
        $this->psr3Logger
            ->expects($this->once())
            ->method('debug')
            ->with('Debug message', ['key' => 'value']);

        $this->logger->debug('Debug message', ['key' => 'value']);

        // Test info
        $this->psr3Logger
            ->expects($this->once())
            ->method('info')
            ->with('Info message', []);

        $this->logger->info('Info message');

        // Test warning
        $this->psr3Logger
            ->expects($this->once())
            ->method('warning')
            ->with('Warning message', []);

        $this->logger->warning('Warning message');

        // Test error
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
