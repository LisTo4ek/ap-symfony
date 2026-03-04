<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use function dirname;

class CurrencyRateProviderBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
