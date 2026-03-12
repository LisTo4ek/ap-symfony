<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Contract for the CurrencyRateBundle logging service.
 *
 * Extends PSR-3 LoggerInterface with domain-specific logging methods for
 * rate retrieval, provider lifecycle, error reporting, and validation.
 */
interface BundleLoggerServiceInterface extends LoggerInterface
{
    /**
     * Extracts a standardized error context array from an exception for logging.
     *
     * @param Throwable $e The exception to extract context from
     * @return array{
     *     class: string,
     *     message: string,
     *     code: (int|string),
     *     trace: string,
     *     previous?: array<string, mixed>
     * } Error details
     */
    public function getExceptionContext(Throwable $e): array;

    /**
     * Returns the underlying PSR-3 logger instance.
     *
     * @return LoggerInterface The Monolog logger for direct access
     */
    public function getLogger(): LoggerInterface;
}
