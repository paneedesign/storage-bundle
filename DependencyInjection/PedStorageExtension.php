<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class PedStorageExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (false === \array_key_exists('amazon_s3', $config) && false === \array_key_exists('local', $config)) {
            throw new \InvalidArgumentException(
                'At least one of the parameters "ped_storage.amazon_s3" or "ped_storage.local" must be set.'
            );
        }
        if (true === \array_key_exists('amazon_s3', $config)) {
            $amazonS3 = $config['amazon_s3'];

            if (false === \array_key_exists('secret', $amazonS3)) {
                throw new \InvalidArgumentException(
                    'The option ped_storage.amazon_s3.secret must be set.'
                );
            }

            if (false === \array_key_exists('endpoint', $amazonS3)) {
                throw new \InvalidArgumentException(
                    'The option ped_storage.amazon_s3.endpoint must be set.'
                );
            }

            if (false === \array_key_exists('bucket_name', $amazonS3)) {
                throw new \InvalidArgumentException(
                    'The option ped_storage.amazon_s3.bucket_name must be set.'
                );
            }

            $container->setParameter('ped_storage.amazon_s3.key', $amazonS3['key']);
            $container->setParameter('ped_storage.amazon_s3.secret', $amazonS3['secret']);
            $container->setParameter('ped_storage.amazon_s3.region', $amazonS3['region']);
            $container->setParameter('ped_storage.amazon_s3.endpoint', $amazonS3['endpoint']);
            $container->setParameter('ped_storage.amazon_s3.bucket_name', $amazonS3['bucket_name']);
            $container->setParameter('ped_storage.amazon_s3.directory', $amazonS3['directory']);
            $container->setParameter('ped_storage.amazon_s3.expire_at', $amazonS3['expire_at']);
            $container->setParameter('ped_storage.amazon_s3.thumbs_prefix', $amazonS3['thumbs_prefix']);
        }

        if (true === \array_key_exists('local', $config)) {
            $local = $config['local'];

            if (false === \array_key_exists('directory', $local)) {
                throw new \InvalidArgumentException(
                    'The option ped_storage.local.directory must be set.'
                );
            }

            $container->setParameter('ped_storage.local.endpoint', $local['endpoint']);
            $container->setParameter('ped_storage.local.web_root_dir', $local['web_root_dir']);
            $container->setParameter('ped_storage.local.directory', $local['directory']);
            $container->setParameter('ped_storage.local.thumbs_prefix', $local['thumbs_prefix']);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
