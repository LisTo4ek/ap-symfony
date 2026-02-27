<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Type;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CurrencyRateProviderBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function boot(): void
    {
        if ($this->container instanceof ContainerInterface) {
            CurrencyIso4217Type::setContainer($this->container);
        }
    }
}
