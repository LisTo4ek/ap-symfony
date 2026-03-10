<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle;

use App\Bundle\CurrencyRateBundle\DependencyInjection\CurrencyRateExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle that provides currency rate import, storage, and display functionality.
 *
 * Registers the CurrencyRateExtension to load bundle configuration,
 * Doctrine entity mappings, migration paths, and monolog channels.
 */
class CurrencyRateBundle extends Bundle
{
    /**
     * Returns the bundle's container extension for dependency injection configuration.
     *
     * @return ExtensionInterface|null The CurrencyRateExtension instance
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new CurrencyRateExtension();
    }
}
