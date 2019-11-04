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
            ->validate()
            ->ifTrue(function ($v) {
                $requiredSettings = [];

                switch ($v['adapter']) {
                    case 'local':
                        $requiredSettings = ['local'];
                        break;
                    case 'amazon':
                        $requiredSettings = ['amazon_s3'];
                        break;
                }

                foreach ($requiredSettings as $setting) {
                    if (!array_key_exists($setting, $v)) {
                        return true;
                    }
                }

                return false;
            })
            ->thenInvalid('Missing required options for "%s"')
            ->end()
            ->children()
                ->enumNode('adapter')
                    ->defaultValue('local')
                    ->values(['local', 'amazon'])
                    ->info('Name of adapter to use to store media')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->append($this->addAmazonS3Section())
                ->append($this->addLocalSection())
            ->end();

        return $treeBuilder;
    }

    private function addAmazonS3Section()
    {
        $treeBuilder = new TreeBuilder('amazon_s3');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $node = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $treeBuilder->root('amazon_s3');
        }

        $node
            ->info('Section can be enabled to store media on Amazon S3')
            ->canBeEnabled()
            ->children()
                ->scalarNode('key')
                    ->info('Amazon S3 Access key ID')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('secret')
                    ->info('Amazon S3 Secret Access key')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('region')
                    ->defaultValue('eu-west-1')
                    ->info('Amazon S3 Region')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('endpoint')
                    ->defaultValue('https://s3.eu-west-1.amazonaws.com')
                    ->info('Amazon S3 Endpoint')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('bucket_name')
                    ->info('Amazon S3 Bucket name')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('directory')
                    ->info('Folder where store media')
                    ->defaultValue('uploads')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('expire_at')
                    ->info('Max age of a media in a Presigned URL Resolver')
                    ->defaultValue('+1 hour')
                ->end()
                ->scalarNode('thumbs_prefix')
                    ->info('Folder where store thumbnails')
                    ->defaultValue('thumbs')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $node;
    }

    private function addLocalSection()
    {
        $treeBuilder = new TreeBuilder('local');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $node = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $treeBuilder->root('local');
        }

        $node
            ->info('Section can be enabled to store media on local filesystem')
            ->canBeEnabled()
            ->children()
                ->scalarNode('endpoint')
                    ->defaultValue('uploads')
                    ->info('The default endpoint to where store media')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('directory')
                    ->defaultValue('%kernel.project_dir%/web/uploads')
                    ->info('Folder where store media')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('thumbs_prefix')
                    ->defaultValue('thumbs')
                    ->info('Folder where store thumbnails')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $node;
    }
}
