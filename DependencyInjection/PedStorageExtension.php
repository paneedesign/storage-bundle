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

        if(array_key_exists('amazon_s3', $config) === false && array_key_exists('local', $config) === false) {
            throw new \InvalidArgumentException(
                'At least one of the parameters "ped_storage.amazon_s3" or "ped_storage.local" must be set.'
            );
        } else {
            if(array_key_exists('amazon_s3', $config) === true) {
                $amazonS3 = $config['amazon_s3'];

                if(array_key_exists('key', $amazonS3) === false) {
                    throw new \InvalidArgumentException('The option "ped_storage.amazon_s3.key" must be set.');
                }

                if(array_key_exists('secret', $amazonS3) === false) {
                    throw new \InvalidArgumentException('The option "ped_storage.amazon_s3.secret" must be set.');
                }

                if(array_key_exists('base_url', $amazonS3) === false) {
                    throw new \InvalidArgumentException('The option "ped_storage.amazon_s3.base_url" must be set.');
                }

                if(array_key_exists('bucket_name', $amazonS3) === false) {
                    throw new \InvalidArgumentException('The option "ped_storage.amazon_s3.bucket_name" must be set.');
                }

                $container->setParameter('ped_storage.amazon_s3.key', $amazonS3['key']);
                $container->setParameter('ped_storage.amazon_s3.secret', $amazonS3['secret']);
                $container->setParameter('ped_storage.amazon_s3.region', $amazonS3['region']);
                $container->setParameter('ped_storage.amazon_s3.base_url', $amazonS3['base_url']);
                $container->setParameter('ped_storage.amazon_s3.bucket_name', $amazonS3['bucket_name']);
                $container->setParameter('ped_storage.amazon_s3.directory', $amazonS3['directory']);
            }

            if(array_key_exists('local', $config) === true) {
                $local = $config['local'];

                if(array_key_exists('directory', $local) === false) {
                    throw new \InvalidArgumentException('The option "ped_storage.local.directory" must be set.');
                }

                $container->setParameter('ped_storage.local.directory', $local['directory']);
            }
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'ped_storage';
    }
}