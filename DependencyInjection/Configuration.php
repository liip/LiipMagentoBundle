<?php

namespace Liip\MagentoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('liip_magento');

        $rootNode
            ->fixXmlConfig('store_mapping', 'store_mappings')
            ->children()
                ->scalarNode('mage_file')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('session_namespace')->defaultValue('frontend')->cannotBeEmpty()->end()
                ->arrayNode('store_mappings')
                    ->addDefaultsIfNotSet()
                    ->useAttributeAsKey('name')
                    ->defaultValue(array())
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('service')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('store_resolver')->defaultValue('liip_magento.store_resolver.default')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
