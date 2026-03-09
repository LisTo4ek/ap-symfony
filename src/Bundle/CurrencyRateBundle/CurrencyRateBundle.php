<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle;

use App\Bundle\CurrencyRateBundle\DependencyInjection\CurrencyRateExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

class CurrencyRateBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new CurrencyRateExtension();
    }
}
