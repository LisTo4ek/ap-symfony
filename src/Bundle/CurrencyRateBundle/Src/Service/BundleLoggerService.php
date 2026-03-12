<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;

use function get_class;

/**
 * Logging service for the CurrencyRateBundle.
 *
 * Wraps a PSR-3 LoggerInterface (targeted to the currency_rate_bundle Monolog channel)
 * and provides domain-specific logging methods for rate retrieval, provider initialization,
 * rate updates, errors, and validation failures. Also delegates all standard PSR-3 log levels.
 *
 * @property LoggerInterface $logger The Monolog logger for the currency_rate_bundle channel
 */
#[AsAlias(BundleLoggerServiceInterface::class)]
class BundleLoggerService implements BundleLoggerServiceInterface
{
    public function __construct(
        #[Target('monolog.logger.currency_rate_bundle')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function getExceptionContext(Throwable $e): array
    {
        $res = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString(),
        ];

        if ($e->getPrevious() !== null) {
            $res['previous'] = $this->getExceptionContext($e->getPrevious());
        }

        return $res;
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
