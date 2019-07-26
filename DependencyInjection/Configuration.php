<?php

declare(strict_types=1);

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
        $treeBuilder = new TreeBuilder('ped_storage');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('ped_storage');
        }

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
                        ->scalarNode('expire_at')->defaultValue('+1 hour')->end()
                        ->scalarNode('thumbs_prefix')->defaultValue('thumbs')->end()
                    ->end()
                ->end()
                ->arrayNode('local')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('endpoint')->defaultValue('uploads')->end()
                        ->scalarNode('web_root_dir')->defaultValue('%kernel.project_dir%/public')->end()
                        ->scalarNode('directory')->defaultValue('%kernel.project_dir%/public/uploads')->end()
                        ->scalarNode('thumbs_prefix')->defaultValue('thumbs')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
