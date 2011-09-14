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
            ->children()
                ->scalarNode('file_mage')->isRequired()->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
