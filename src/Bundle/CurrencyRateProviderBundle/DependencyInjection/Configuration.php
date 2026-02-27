<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\DependencyInjection;

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
                        ->end()
                        ->integerNode('timeout')
                            ->isRequired()
                        ->end()
                        ->integerNode('retry_attempts')
                            ->isRequired()
                        ->end()
                        ->integerNode('rate_precision')
                            ->info('Number of decimal places for currency rates')
                            ->isRequired()
                        ->end()
                        ->scalarNode('base_currency')
                            ->info('Base currency code (e.g., USD, EUR, RUB)')
                            ->isRequired()
                        ->end()
                        ->arrayNode('monitored_currencies')
                            ->performNoDeepMerging()
                            ->info('List of currency codes to monitor')
                            ->isRequired()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

