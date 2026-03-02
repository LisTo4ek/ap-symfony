<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception;

/**
 * Base exception for currency rate provider failures
 *
 * This is the main exception type for rate retrieval and processing errors.
 * Status Code: 503 Service Unavailable
 */
class ProviderException extends CurrencyRateProviderBundleException
{
    protected int $statusCode = 503;
}

