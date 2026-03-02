<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;

class CurrencyRateProviderExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $pattern = 'currency_rate_provider.cbr_provider.%s';
        $container->setParameter(sprintf($pattern, 'api_url'), $config['cbr_provider']['api_url']);
        $container->setParameter(sprintf($pattern, 'timeout'), $config['cbr_provider']['timeout']);
        $container->setParameter(sprintf($pattern, 'rate_precision'), $config['cbr_provider']['rate_precision']);
        $container->setParameter(sprintf($pattern, 'base_currency'), $config['cbr_provider']['base_currency']);
        $container->setParameter(sprintf($pattern, 'monitored_currencies'), $config['cbr_provider']['monitored_currencies']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configPath = dirname(__DIR__) . '/Resources/config/monolog.yaml';

        if (!file_exists($configPath)) {
            throw new RuntimeException(sprintf('Monolog configuration file not found at: %s', $configPath));
        }

        $monologConfig = Yaml::parseFile($configPath);
        $env = $container->getParameter('kernel.environment');

        if (isset($monologConfig['monolog'])) {
            $container->prependExtensionConfig('monolog', $monologConfig['monolog']);
        }

        $envKey = 'when@' . $env;
        if (isset($monologConfig[$envKey]['monolog'])) {
            $container->prependExtensionConfig('monolog', $monologConfig[$envKey]['monolog']);
        }
    }

    public function getPath(): string
    {
        return __DIR__;
    }

    public function getAlias(): string
    {
        return 'currency_rate_provider';
    }
}
