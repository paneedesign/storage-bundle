<?php

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ped_storage');

        $rootNode
            ->children()
                ->arrayNode('amazon_s3')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('key')->defaultValue('')->end()
                        ->scalarNode('secret')->defaultValue('')->end()
                        ->scalarNode('region')->defaultValue('eu-west-1')->end()
                        ->scalarNode('endpoint')->defaultValue('')->end()
                        ->scalarNode('bucket_name')->defaultValue('')->end()
                        ->scalarNode('directory')->defaultValue('uploads')->end()
                    ->end()
                ->end()
                ->arrayNode('local')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.root_dir%/../web/uploads')->end()
                        ->scalarNode('endpoint')->defaultValue('uploads')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
