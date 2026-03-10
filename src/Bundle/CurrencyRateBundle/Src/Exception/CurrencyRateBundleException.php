<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Exception;

use Exception;
use Throwable;

/**
 * Base exception for all CurrencyRateProviderBundle errors
 */
class CurrencyRateBundleException extends Exception
{
    /**
     * HTTP status code associated with this exception
     * Useful for API responses
     */
    protected int $statusCode = 500;

    /**
     * @param string         $message  The exception message
     * @param int            $code     The exception code
     * @param Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code for this exception
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
