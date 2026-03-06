<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTime;
use Throwable;

/**
 * CurrencyRateProviderLoggerInterface defines the contract for logging in the CurrencyRateProviderBundle
 */
interface CurrencyRateProviderLoggerInterface
{
    /**
     * Log a rate retrieval operation
     */
    public function logRateRetrieval(string $from, string $to, string $rate, int $durationMs = 0): void;

    /**
     * Log a provider initialization
     */
    public function logProviderInit(string $providerName, array $config = []): void;

    /**
     * Log a rate update operation
     */
    public function logRateUpdate(string $currency, string $rate, DateTime $timestamp): void;

    /**
     * Log a provider error
     */
    public function logProviderError(string $providerName, string $message, ?Throwable $exception = null): void;

    /**
     * Log a rate retrieval failure
     */
    public function logRetrievalFailure(string $from, string $to, string $reason, int $attempt = 0): void;

    /**
     * Log a validation error
     */
    public function logValidationError(array $errors): void;

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a notice message
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log an alert message
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log an emergency message
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log at the specified level
     */
    public function log(string $level, string $message, array $context = []): void;
}

