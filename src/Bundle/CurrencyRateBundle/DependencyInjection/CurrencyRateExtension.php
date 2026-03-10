<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;

use function dirname;
use function file_exists;
use function sprintf;

/**
 * Dependency injection extension for the CurrencyRateBundle.
 *
 * Loads and processes the bundle configuration, registers container parameters,
 * and prepends configuration for Monolog, Doctrine ORM mappings, custom DBAL types,
 * and Doctrine Migrations paths.
 */
class CurrencyRateExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Loads the bundle configuration and registers container parameters.
     *
     * Processes the validated configuration tree and sets parameters for
     * rate precision, CBR provider API URL, timeout, base currency,
     * and monitored currencies.
     *
     * @param array<int, array<string, mixed>> $configs   Raw configuration arrays from various sources
     * @param ContainerBuilder                 $container The service container builder
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $pattern = 'currency_rate_provider.%s';
        $container->setParameter(
            sprintf($pattern, 'calculate_rate_precision'),
            $config['calculate_rate_precision'],
        );
        $container->setParameter(
            sprintf($pattern, 'display_rate_precision'),
            $config['display_rate_precision'],
        );

        $sectionConfig = $config['cbr_provider'];
        $cbrProviderPattern = 'currency_rate_provider.cbr_provider.%s';
        $container->setParameter(sprintf($cbrProviderPattern, 'api_url'), $sectionConfig['api_url']);
        $container->setParameter(sprintf($cbrProviderPattern, 'timeout'), $sectionConfig['timeout']);
        $container->setParameter(sprintf($cbrProviderPattern, 'base_currency'), $sectionConfig['base_currency']);
        $container->setParameter(
            sprintf($cbrProviderPattern, 'monitored_currencies'),
            $sectionConfig['monitored_currencies'],
        );
    }

    /**
     * Prepends configuration for third-party bundles before the container is compiled.
     *
     * Registers Monolog logging channels, Doctrine ORM entity mappings,
     * custom DBAL types (MoneyCurrencyType, BigDecimalStringType),
     * and Doctrine Migrations namespace paths.
     *
     * @param ContainerBuilder $container The service container builder
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependMonolog($container);
        $this->prependDoctrine($container);
        $this->prependDoctrineMigrations($container);
    }

    /**
     * Prepends Monolog configuration from the bundle's Resources/config/monolog.yaml.
     *
     * Loads both the base monolog configuration and environment-specific
     * configuration (e.g., when@dev, when@test) if present.
     *
     * @param ContainerBuilder $container The service container builder
     *
     * @throws RuntimeException If the monolog config file is missing or not an array
     */
    private function prependMonolog(ContainerBuilder $container): void
    {
        $configPath = dirname(__DIR__) . '/Resources/config/monolog.yaml';

        if (!file_exists($configPath)) {
            throw new RuntimeException(sprintf('Monolog configuration file not found at: %s', $configPath));
        }

        $monologConfig = Yaml::parseFile($configPath);

        if (!is_array($monologConfig)) {
            throw new RuntimeException('Monolog configuration must be an array');
        }

        $env = $container->getParameter('kernel.environment');

        if (isset($monologConfig['monolog']) && is_array($monologConfig['monolog'])) {
            /** @var array<string, mixed> $monologConfigArray */
            $monologConfigArray = $monologConfig['monolog'];
            $container->prependExtensionConfig('monolog', $monologConfigArray);
        }

        if (is_string($env)) {
            $envKey = 'when@' . $env;
            if (isset($monologConfig[$envKey]['monolog']) && is_array($monologConfig[$envKey])) {
                /** @var array<string, mixed> $monologEnvConfig */
                $monologEnvConfig = $monologConfig[$envKey]['monolog'];
                $container->prependExtensionConfig('monolog', $monologEnvConfig);
            }
        }
    }

    /**
     * Prepends Doctrine ORM entity mappings and custom DBAL types.
     *
     * Registers the bundle's entity directory with attribute-based mapping
     * and adds custom DBAL types for Money\Currency and Brick\Math\BigDecimal.
     *
     * @param ContainerBuilder $container The service container builder
     */
    private function prependDoctrine(ContainerBuilder $container): void
    {
        /** @var array<string, mixed> $doctrineConfig */
        $doctrineConfig = [
            'orm' => [
                'mappings' => [
                    'CurrencyRateBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => dirname(__DIR__) . '/Src/Entity',
                        'prefix' => 'App\Bundle\CurrencyRateBundle\Src\Entity',
                        'alias' => 'CurrencyRateBundle',
                    ],
                ],
            ],
            'dbal' => [
                'types' => [
                    'money_currency' => 'App\Bundle\CurrencyRateBundle\Src\Entity\MoneyCurrencyType',
                    'big_decimal_string' => 'App\Bundle\CurrencyRateBundle\Src\Entity\BigDecimalStringType',
                ],
            ],
        ];
        $container->prependExtensionConfig('doctrine', $doctrineConfig);
    }

    /**
     * Prepends Doctrine Migrations configuration with the bundle's migration namespace and path.
     *
     * Skips registration if the doctrine_migrations extension is not loaded.
     *
     * @param ContainerBuilder $container The service container builder
     */
    private function prependDoctrineMigrations(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine_migrations')) {
            return;
        }

        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                'App\\Bundle\\CurrencyRateBundle\\Migrations' => dirname(__DIR__) . '/Migrations',
            ],
        ]);
    }

    /**
     * Returns the extension alias used in configuration files.
     *
     * @return string The alias 'currency_rate_provider'
     */
    public function getAlias(): string
    {
        return 'currency_rate_provider';
    }
}
