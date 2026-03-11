<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Contract for the CurrencyRateBundle logging service.
 *
 * Extends PSR-3 LoggerInterface with domain-specific logging methods for
 * rate retrieval, provider lifecycle, error reporting, and validation.
 */
interface ProviderLoggerServiceInterface extends LoggerInterface
{
    /**
     * Log a successful rate retrieval operation.
     *
     * @param string $from The source currency code
     * @param string $to The target currency code
     * @param string $rate The retrieved rate value
     * @param int $durationMs Duration of the retrieval in milliseconds
     */
    public function logRateRetrieval(string $from, string $to, string $rate, int $durationMs = 0): void;

    /**
     * Log a provider initialization event.
     *
     * @param string $providerName The provider name (e.g. 'CBR')
     * @param array<string, mixed> $config Configuration keys used during initialization
     */
    public function logProviderInit(string $providerName, array $config = []): void;

    /**
     * Log a rate update operation.
     *
     * @param string $currency The currency code being updated
     * @param string $rate The new rate value
     * @param DateTime $timestamp The time of the update
     */
    public function logRateUpdate(string $currency, string $rate, DateTime $timestamp): void;

    /**
     * Log a provider-level error.
     *
     * @param string $providerName The provider name that encountered the error
     * @param string $message A human-readable error description
     * @param Throwable|null $exception The underlying exception, if any
     */
    public function logProviderError(string $providerName, string $message, ?Throwable $exception = null): void;

    /**
     * Log a rate retrieval failure with retry context.
     *
     * @param string $from The source currency code
     * @param string $to The target currency code
     * @param string $reason The reason for the failure
     * @param int $attempt The retry attempt number (0 = first attempt)
     */
    public function logRetrievalFailure(string $from, string $to, string $reason, int $attempt = 0): void;

    /**
     * Log a validation error.
     *
     * @param array<string, mixed> $errors Validation error details
     */
    public function logValidationError(array $errors): void;
}
