<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception;

/**
 * Exception thrown when rate data is invalid or corrupted
 *
 * This exception indicates that the data received from the provider
 * is malformed, incomplete, or cannot be processed.
 *
 * Status Code: 422 Unprocessable Entity
 *
 * Examples:
 * - Missing required XML elements
 * - Invalid currency codes
 * - Malformed rate values
 * - Duplicate currencies
 */
class InvalidRateDataException extends ProviderException
{
    protected int $statusCode = 422;
}

