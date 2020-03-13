<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\DependencyInjection;

use PaneeDesign\StorageBundle\Utility\Framework\SymfonyFramework;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public const LOCAL_ADAPTER = 'local';
    public const AMAZON_S3_ADAPTER = 'amazon_s3';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ped_storage');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {
                    $requiredSettings = [];

                    if (self::AMAZON_S3_ADAPTER === $v['adapter']) {
                        $requiredSettings[] = $v['adapter'];
                    }

                    foreach ($requiredSettings as $setting) {
                        if (
                            !\array_key_exists($setting, $v) ||
                            (\array_key_exists($setting, $v) && false === $v[$setting]['enabled'])
                        ) {
                            return true;
                        }
                    }

                    return false;
                })
                ->then(function ($v) {
                    throw new \InvalidArgumentException(sprintf('Missing required option \'%s\'', $v['adapter']));
                })
            ->end()
            ->children()
                ->enumNode('adapter')
                    ->defaultValue(self::LOCAL_ADAPTER)
                    ->values([self::LOCAL_ADAPTER, self::AMAZON_S3_ADAPTER])
                    ->info('Name of adapter to use to store media')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('directory')
                    ->defaultValue('uploads')
                    ->info('Folder where store media')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('thumbs_prefix')
                    ->defaultValue('thumbs')
                    ->info('Folder where store thumbnails')
                    ->cannotBeEmpty()
                ->end()
                ->append($this->addAmazonS3Section())
                ->append($this->addLocalSection())
            ->end();

        return $treeBuilder;
    }

    private function addAmazonS3Section()
    {
        $treeBuilder = new TreeBuilder(self::AMAZON_S3_ADAPTER);
        $node = $treeBuilder->getRootNode();

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
                ->scalarNode('expire_at')
                    ->info('Max age of a media in a Presigned URL Resolver')
                    ->defaultValue('+1 hour')
                ->end()
            ->end();

        return $node;
    }

    private function addLocalSection()
    {
        $treeBuilder = new TreeBuilder(self::LOCAL_ADAPTER);
        $node = $treeBuilder->getRootNode();

        $node
            ->info('Section can be enabled to store media on local filesystem')
            ->canBeEnabled()
            ->children()
                ->scalarNode('endpoint')
                    ->defaultValue('/uploads')
                    ->info('The default endpoint to where retrive media')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('web_root_dir')
                    ->defaultValue(SymfonyFramework::getContainerResolvableRootWebPath())
                    ->info('Root where store media (%kernel.project_dir%/web for Symfony < 4.0.0 otherwise %kernel.project_dir%/public)')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $node;
    }
}
