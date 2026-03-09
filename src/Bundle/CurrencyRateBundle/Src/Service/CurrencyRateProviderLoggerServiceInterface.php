<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

interface CurrencyRateProviderLoggerServiceInterface extends LoggerInterface
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
}
