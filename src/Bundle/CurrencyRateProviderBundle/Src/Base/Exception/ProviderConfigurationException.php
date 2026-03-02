<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception;

/**
 * Exception thrown when the provider is misconfigured
 *
 * This exception indicates configuration issues that prevent
 * the provider from functioning properly. These are typically
 * non-transient errors that require manual intervention.
 *
 * Status Code: 500 Internal Server Error
 *
 * Examples:
 * - Missing required configuration parameters
 * - Invalid API URL format
 * - Invalid timeout values
 * - API returns unexpected response structure
 */
class ProviderConfigurationException extends ProviderException
{
    protected int $statusCode = 500;
}

