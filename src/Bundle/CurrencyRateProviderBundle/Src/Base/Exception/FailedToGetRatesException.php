<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Exception;

use Exception;

class FailedToGetRatesException extends Exception
{
    public function __construct(string $message = "Failed to get rates", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
