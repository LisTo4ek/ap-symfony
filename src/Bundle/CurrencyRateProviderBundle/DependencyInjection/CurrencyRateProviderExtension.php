<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class CurrencyRateProviderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Set parameters from configuration (keep codes as strings)
        $pattern = 'currency_rate_provider.cbr_provider.%s';
        $container->setParameter(sprintf($pattern, 'api_url'), $config['cbr_provider']['api_url']);
        $container->setParameter(sprintf($pattern, 'timeout'), $config['cbr_provider']['timeout']);
        $container->setParameter(sprintf($pattern, 'retry_attempts'), $config['cbr_provider']['retry_attempts']);
        $container->setParameter(sprintf($pattern, 'rate_precision'), $config['cbr_provider']['rate_precision']);
        $container->setParameter(sprintf($pattern, 'base_currency'), $config['cbr_provider']['base_currency']);
        $container->setParameter(sprintf($pattern, 'monitored_currencies'), $config['cbr_provider']['monitored_currencies']);
    }

    public function getAlias(): string
    {
        return 'currency_rate_provider';
    }
}
