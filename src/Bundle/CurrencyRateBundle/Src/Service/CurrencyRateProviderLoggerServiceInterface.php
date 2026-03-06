<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTime;
use Throwable;

interface CurrencyRateProviderLoggerServiceInterface
{
    /**
     * Log a rate retrieval operation
     */
    public function logRateRetrieval(string $from, string $to, string $rate, int $durationMs = 0): void;

    /**
     * Log a provider initialization
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $errors
     */
    public function logValidationError(array $errors): void;

    /**
     * Log a debug message
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log an info message
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a notice message
     * @param array<string, mixed> $context
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log a warning message
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a critical message
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log an alert message
     * @param array<string, mixed> $context
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log an emergency message
     * @param array<string, mixed> $context
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log at the specified level
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void;
}
