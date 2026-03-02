<?php

declare(strict_types=1);

namespace App\Trait;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * LoggerAwareTrait provides convenient logging methods
 *
 * Usage in a class:
 * ```php
 * class MyService {
 *     use LoggerAwareTrait;
 *
 *     public function __construct(private LoggerInterface $logger) {}
 *
 *     public function doSomething() {
 *         $this->log('Operation started');
 *         $this->logInfo('Status update', ['count' => 10]);
 *     }
 * }
 * ```
 */
trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    /**
     * Set the logger instance
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Log a message at the default level (INFO)
     */
    protected function log(string $message, array $context = []): void
    {
        $this->logger->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Log a debug message
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log a notice message
     */
    protected function logNotice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Log a warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log a critical message
     */
    protected function logCritical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log an alert message
     */
    protected function logAlert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Log an emergency message
     */
    protected function logEmergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Log an exception with context
     */
    protected function logException(\Throwable $exception, string $message = '', array $context = []): void
    {
        $context['exception'] = $exception;
        $message = $message ?: 'An exception occurred: ' . $exception->getMessage();
        $this->logger->error($message, $context);
    }
}

