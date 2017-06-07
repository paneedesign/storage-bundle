<?php

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class PedStorageExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        if (!isset($config['amazon_s3']['key'])) {
            throw new \InvalidArgumentException(
                'The option "ped_storage.amazon_s3.key" must be set.'
            );
        }
        
        $container->setParameter(
            'ped_storage.amazon_s3.key',
            $config['amazon_s3']['key']
        );

        if (!isset($config['amazon_s3']['secret'])) {
            throw new \InvalidArgumentException(
                'The option "ped_storage.amazon_s3.secret" must be set.'
            );
        }
        
        $container->setParameter(
            'ped_storage.amazon_s3.secret',
            $config['amazon_s3']['secret']
        );

        if (!isset($config['amazon_s3']['region'])) {
            throw new \InvalidArgumentException(
                'The option "ped_storage.amazon_s3.region" must be set.'
            );
        }

        $container->setParameter(
            'ped_storage.amazon_s3.region',
            $config['amazon_s3']['region']
        );

        if (!isset($config['amazon_s3']['base_url'])) {
            throw new \InvalidArgumentException(
                'The option "ped_storage.amazon_s3.base_url" must be set.'
            );
        }
        
        $container->setParameter(
            'ped_storage.amazon_s3.base_url',
            $config['amazon_s3']['base_url']
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'ped_storage';
    }
}
