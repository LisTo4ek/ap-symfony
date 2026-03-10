<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use DateTime;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;

use function count;

/**
 * Logging service for the CurrencyRateBundle.
 *
 * Wraps a PSR-3 LoggerInterface (targeted to the currency_rate_bundle Monolog channel)
 * and provides domain-specific logging methods for rate retrieval, provider initialization,
 * rate updates, errors, and validation failures. Also delegates all standard PSR-3 log levels.
 */
#[AsAlias(ProviderLoggerServiceInterface::class)]
class ProviderLoggerService implements ProviderLoggerServiceInterface
{
    /**
     * @param LoggerInterface $logger The Monolog logger for the currency_rate_bundle channel
     */
    public function __construct(
        #[Target('monolog.logger.currency_rate_bundle')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Logs a successful exchange rate retrieval.
     *
     * @param string $from       The source currency code
     * @param string $to         The target currency code
     * @param string $rate       The retrieved rate value
     * @param int    $durationMs Duration of the retrieval operation in milliseconds
     */
    public function logRateRetrieval(string $from, string $to, string $rate, int $durationMs = 0): void
    {
        $this->logger->info('Exchange rate retrieved', [
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Logs a provider initialization event with configuration details.
     *
     * @param string               $providerName The provider name (e.g. 'CBR')
     * @param array<string, mixed> $config       Configuration parameters used during initialization
     */
    public function logProviderInit(string $providerName, array $config = []): void
    {
        $this->logger->info('Provider initialized', [
            'provider' => $providerName,
            'config_keys' => array_keys($config),
        ]);
    }

    /**
     * Logs a currency rate update event.
     *
     * @param string   $currency  The currency code that was updated
     * @param string   $rate      The new rate value
     * @param DateTime $timestamp The time of the update
     */
    public function logRateUpdate(string $currency, string $rate, DateTime $timestamp): void
    {
        $this->logger->info('Currency rate updated', [
            'currency' => $currency,
            'rate' => $rate,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Logs a provider-level error.
     *
     * @param string         $providerName The name of the provider that encountered the error
     * @param string         $message      A human-readable error description
     * @param Throwable|null $exception    The underlying exception, if any
     */
    public function logProviderError(string $providerName, string $message, ?Throwable $exception = null): void
    {
        $context = [
            'provider' => $providerName,
            'error_message' => $message,
        ];

        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
            $context['exception_code'] = $exception->getCode();
        }

        $this->logger->error('Provider error', $context);
    }

    /**
     * Logs a rate retrieval failure with retry information.
     *
     * @param string $from    The source currency code
     * @param string $to      The target currency code
     * @param string $reason  The reason for the failure
     * @param int    $attempt The retry attempt number (0 = first attempt)
     */
    public function logRetrievalFailure(string $from, string $to, string $reason, int $attempt = 0): void
    {
        $this->logger->warning('Rate retrieval failed', [
            'from' => $from,
            'to' => $to,
            'reason' => $reason,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Logs a validation error with the error details and count.
     *
     * @param array<string, mixed> $errors Validation error details
     */
    public function logValidationError(array $errors): void
    {
        $this->logger->warning('Validation failed', [
            'errors' => $errors,
            'error_count' => count($errors),
        ]);
    }

    /**
     * Returns the underlying PSR-3 logger instance.
     *
     * @return LoggerInterface The Monolog logger for direct access
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
