<?php

declare(strict_types=1);

namespace App\Service\Logger;

use Psr\Log\LoggerInterface;

/**
 * LoggerFactory provides convenient access to all logging channels
 */
class LoggerFactory
{
    public function __construct(
        private LoggerInterface $defaultLogger,
        private LoggerInterface $databaseLogger,
        private LoggerInterface $apiLogger,
        private LoggerInterface $securityLogger,
        private LoggerInterface $currencyLogger,
        private LoggerInterface $performanceLogger,
        private LoggerInterface $mailLogger,
    ) {
    }

    /**
     * Get the default logger
     */
    public function getDefaultLogger(): LoggerInterface
    {
        return $this->defaultLogger;
    }

    /**
     * Get the database logger for database-related operations
     */
    public function getDatabaseLogger(): LoggerInterface
    {
        return $this->databaseLogger;
    }

    /**
     * Get the API logger for API requests/responses
     */
    public function getApiLogger(): LoggerInterface
    {
        return $this->apiLogger;
    }

    /**
     * Get the security logger for authentication/authorization
     */
    public function getSecurityLogger(): LoggerInterface
    {
        return $this->securityLogger;
    }

    /**
     * Get the currency logger for currency operations
     */
    public function getCurrencyLogger(): LoggerInterface
    {
        return $this->currencyLogger;
    }

    /**
     * Get the performance logger for performance metrics
     */
    public function getPerformanceLogger(): LoggerInterface
    {
        return $this->performanceLogger;
    }

    /**
     * Get the mail logger for email operations
     */
    public function getMailLogger(): LoggerInterface
    {
        return $this->mailLogger;
    }

    /**
     * Get a logger by channel name
     */
    public function getLogger(string $channel): LoggerInterface
    {
        return match ($channel) {
            'default' => $this->defaultLogger,
            'database' => $this->databaseLogger,
            'api' => $this->apiLogger,
            'security' => $this->securityLogger,
            'currency' => $this->currencyLogger,
            'performance' => $this->performanceLogger,
            'mail' => $this->mailLogger,
            default => $this->defaultLogger,
        };
    }
}

