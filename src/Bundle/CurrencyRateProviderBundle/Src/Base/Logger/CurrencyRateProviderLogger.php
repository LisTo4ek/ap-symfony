<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger;

use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;
use function count;

/**
 * CurrencyRateProviderLogger provides logging functionality for the CurrencyRateProviderBundle
 */
#[AsAlias(CurrencyRateProviderLoggerContract::class)]
class CurrencyRateProviderLogger implements CurrencyRateProviderLoggerContract
{

    public function __construct(
        #[Target('monolog.logger.currency_rate_provider')]
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

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}


