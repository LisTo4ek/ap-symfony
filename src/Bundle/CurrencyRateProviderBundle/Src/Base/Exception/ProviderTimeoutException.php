<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception;

/**
 * Exception thrown when a request times out
 *
 * This exception indicates that a request to the provider
 * took longer than the configured timeout period.
 *
 * Status Code: 408 Request Timeout
 */
class ProviderTimeoutException extends ProviderException
{
    protected int $statusCode = 408;
}

