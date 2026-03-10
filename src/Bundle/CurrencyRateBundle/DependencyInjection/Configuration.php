<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration tree for the CurrencyRateBundle.
 *
 * Validates and structures bundle settings including rate precision,
 * CBR provider API URL, timeout, base currency, and monitored currencies.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Builds and returns the configuration tree for the 'currency_rate_provider' extension.
     *
     * Configuration keys:
     * - calculate_rate_precision: non-negative int (0–38), decimal places for internal rate calculations
     * - display_rate_precision: non-negative int (0–38), decimal places for displayed rates
     * - cbr_provider.api_url: valid URL for the CBR API endpoint
     * - cbr_provider.timeout: positive int, HTTP request timeout in seconds
     * - cbr_provider.base_currency: 3-letter ISO 4217 currency code (e.g. RUB)
     * - cbr_provider.monitored_currencies: non-empty list of 3-letter ISO 4217 currency codes
     *
     * @return TreeBuilder The configuration tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('currency_rate_provider');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->integerNode('calculate_rate_precision')
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
                ->integerNode('display_rate_precision')
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
                                    ->thenInvalid(
                                        'Each currency code must be a 3-letter ISO 4217 code (e.g., USD, EUR)'
                                    )
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
