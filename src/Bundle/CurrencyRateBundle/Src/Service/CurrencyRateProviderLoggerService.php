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
 * CurrencyRateProviderLogger provides logging functionality for the CurrencyRateProviderBundle
 */
#[AsAlias(CurrencyRateProviderLoggerServiceInterface::class)]
class CurrencyRateProviderLoggerService implements CurrencyRateProviderLoggerServiceInterface
{
    public function __construct(
        #[Target('monolog.logger.currency_rate_bundle')]
        private LoggerInterface $logger,
    ) {
    }

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
     * @param array<string, mixed> $config
     */
    public function logProviderInit(string $providerName, array $config = []): void
    {
        $this->logger->info('Provider initialized', [
            'provider' => $providerName,
            'config_keys' => array_keys($config),
        ]);
    }

    public function logRateUpdate(string $currency, string $rate, DateTime $timestamp): void
    {
        $this->logger->info('Currency rate updated', [
            'currency' => $currency,
            'rate' => $rate,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ]);
    }

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
     * Log a validation error
     *
     * @param array<string, mixed> $errors
     */
    public function logValidationError(array $errors): void
    {
        $this->logger->warning('Validation failed', [
            'errors' => $errors,
            'error_count' => count($errors),
        ]);
    }

    /**
     * Get the underlying PSR-3 logger
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
