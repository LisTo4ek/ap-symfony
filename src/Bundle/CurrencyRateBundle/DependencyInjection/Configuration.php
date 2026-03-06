<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('currency_rate_provider');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('cbr_provider')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('api_url')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(fn($v) => empty($v))
                                ->thenInvalid('CBR API URL must not be empty')
                            ->end()
                            ->validate()
                                ->ifTrue(fn($v) => !filter_var($v, FILTER_VALIDATE_URL))
                                ->thenInvalid('CBR API URL must be a valid URL format')
                            ->end()
                        ->end()
                        ->integerNode('timeout')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(fn($v) => $v <= 0)
                                ->thenInvalid('Timeout must be a positive integer (> 0)')
                            ->end()
                        ->end()
                        ->integerNode('rate_precision')
                            ->info('Number of decimal places for currency rates')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(fn($v) => $v < 0)
                                ->thenInvalid('Rate precision must be a non-negative integer (>= 0)')
                            ->end()
                            ->validate()
                                ->ifTrue(fn($v) => $v > 38)
                                ->thenInvalid('Rate precision must not exceed 38 decimal places')
                            ->end()
                        ->end()
                        ->scalarNode('base_currency')
                            ->info('Base currency code (e.g., USD, EUR, RUB)')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(fn($v) => empty($v))
                                ->thenInvalid('Base currency code must not be empty')
                            ->end()
                            ->validate()
                                ->ifTrue(fn($v) => !preg_match('/^[A-Z]{3}$/', $v))
                                ->thenInvalid('Base currency code must be a 3-letter ISO 4217 code (e.g., RUB, USD)')
                            ->end()
                        ->end()
                        ->arrayNode('monitored_currencies')
                            ->performNoDeepMerging()
                            ->info('List of currency codes to monitor')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(fn($v) => empty($v))
                                ->thenInvalid('Monitored currencies list must not be empty')
                            ->end()
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(fn($v) => !preg_match('/^[A-Z]{3}$/', $v))
                                    ->thenInvalid('Each currency code must be a 3-letter ISO 4217 code (e.g., USD, EUR)')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
