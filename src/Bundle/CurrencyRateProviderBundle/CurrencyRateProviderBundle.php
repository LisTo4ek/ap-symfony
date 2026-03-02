<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CurrencyRateProviderBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function boot(): void
    {
    }
}
