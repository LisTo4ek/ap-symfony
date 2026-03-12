<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Constant;

/**
 * Bundle-wide immutable configuration constants for currency rate processing.
 *
 * This class centralizes values that control processing behaviour and are
 * safe to reference across services, commands and jobs. Constants here are
 * intended to be small, stable configuration values (limits, sizes, timeouts)
 * that do not require runtime configuration.
 *
 * @internal
 */
class CommonConstant
{
    /** @const Maximum number of items per processing chunk */
    public const int CHUNK_SIZE = 1000;
}
