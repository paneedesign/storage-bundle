<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Adapter\Local;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
use Gaufrette\Filesystem;
use Liip\ImagineBundle\Binary\Loader\StreamLoader;
use Liip\ImagineBundle\Imagine\Cache\Resolver\AwsS3Resolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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

        if (is_array($config) && \array_key_exists('adapter', $config)) {
            $container->setParameter('ped_storage.adapter', $config['adapter']);
            $container->setParameter('knp_gaufrette.stream_wrapper.protocol', 'pedstorage');

            switch ($config['adapter']) {
                case Configuration::LOCAL_ADAPTER:
                    $this->loadParameters($config['adapter'], $config[Configuration::LOCAL_ADAPTER], $container);
                    $this->loadLocalResolvers($config['adapter'], $container);
                    $this->loadLoaders($config['adapter'], $container);
                    $this->loadExternalResources($config['adapter'], $container);

                    break;
                case Configuration::AMAZON_S3_ADAPTER:
                    $this->loadParameters($config['adapter'], $config[Configuration::AMAZON_S3_ADAPTER], $container);
                    $this->loadAmazonS3Client($container);
                    $this->loadAmazonS3Resolvers($config['adapter'], $container);
                    $this->loadLoaders($config['adapter'], $container);
                    $this->loadExternalResources($config['adapter'], $container);

                    break;
            }

            $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load(sprintf('adapter/%s.yaml', $config['adapter']));
            $loader->load('services.yaml');
        }
    }

    private function loadParameters(string $adapter, array $configs, ContainerBuilder $container)
    {
        foreach ($configs as $key => $value) {
            $container->setParameter(sprintf('ped_storage.%s.%s', $adapter, $key), $value);
        }
    }

    private function loadLocalResolvers(string $adapter, ContainerBuilder $container)
    {
        if ($container->hasParameter('ped_storage.local.thumbs_prefix')) {
            $cacheResolver = new ChildDefinition('liip_imagine.cache.resolver.prototype.web_path');
            $cacheResolver->setArgument('$cachePrefix', $container->getParameter('ped_storage.local.thumbs_prefix'));

            $cacheResolver->addTag('liip_imagine.cache.resolver', [
                'resolver' => sprintf('%s_fs', $adapter),
            ]);

            $container->setDefinition(sprintf('ped_storage.imagine.cache.resolver.%s', $adapter), $cacheResolver);
        }
    }

    private function loadAmazonS3Client(ContainerBuilder $container)
    {
        if ($container->hasParameter('ped_storage.amazon_s3.expire_at')) {
            $expireDateTime = new Definition(\DateTime::class);
            $expireDateTime->addArgument($container->getParameter('ped_storage.amazon_s3.expire_at'));

            $container->setDefinition('expire_at_datetime', $expireDateTime);
        }

        if (
            $container->hasParameter('ped_storage.amazon_s3.region') &&
            $container->hasParameter('ped_storage.amazon_s3.endpoint') &&
            $container->hasParameter('ped_storage.amazon_s3.key') &&
            $container->hasParameter('ped_storage.amazon_s3.secret')
        ) {
            $s3Client = new Definition(S3Client::class);
            $s3Client->setFactory([S3Client::class, 'factory']);
            $s3Client->addArgument([
                'version' => 'latest',
                'region' => $container->getParameter('ped_storage.amazon_s3.region'),
                'endpoint' => $container->getParameter('ped_storage.amazon_s3.endpoint'),
                'credentials' => [
                    'key' => $container->getParameter('ped_storage.amazon_s3.key'),
                    'secret' => $container->getParameter('ped_storage.amazon_s3.secret'),
                ],
            ]);

            $container->setDefinition('ped_storage.amazon_s3.client', $s3Client);
        }
    }

    private function loadAmazonS3Resolvers(string $adapter, ContainerBuilder $container)
    {
        if (
            $container->hasDefinition('ped_storage.amazon_s3.client') &&
            $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
            $container->hasParameter('ped_storage.amazon_s3.directory')
        ) {
            $publicUrlResolver = new Definition(AwsS3PublicUrlResolver::class);
            $publicUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.amazon_s3.client'));
            $publicUrlResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
            $publicUrlResolver->setArgument('$baseDir', $container->getParameter('ped_storage.amazon_s3.directory'));
            $publicUrlResolver->setPublic(true);

            $container->setDefinition('ped_storage.amazon_public_url_resolver', $publicUrlResolver);

            if ($container->hasDefinition('expire_at_datetime')) {
                $presignedUrlResolver = new Definition(AwsS3PresignedUrlResolver::class);
                $presignedUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.amazon_s3.client'));
                $presignedUrlResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
                $presignedUrlResolver->setArgument('$baseDir', $container->getParameter('ped_storage.amazon_s3.directory'));
                $presignedUrlResolver->setArgument('$expiresAt', $container->getDefinition('expire_at_datetime'));
                $presignedUrlResolver->setPublic(true);

                $container->setDefinition('ped_storage.amazon_presigned_url_resolver', $presignedUrlResolver);
            }

            $staticUrlResolver = new Definition(StaticUrlResolver::class);
            $staticUrlResolver->setArgument('$prefix', $container->getParameter('ped_storage.amazon_s3.directory'));
            $staticUrlResolver->setPublic(true);

            $container->setDefinition('ped_storage.amazon_static_url_resolver', $staticUrlResolver);
        }

        if (
            $container->hasDefinition('ped_storage.amazon_s3.client') &&
            $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
            $container->hasParameter('ped_storage.amazon_s3.thumbs_prefix')
        ) {
            $cacheResolver = new Definition(AwsS3Resolver::class);
            $cacheResolver->setArgument('$storage', $container->getDefinition('ped_storage.amazon_s3.client'));
            $cacheResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
            $cacheResolver->addMethodCall('setCachePrefix', [
                $container->getParameter('ped_storage.amazon_s3.thumbs_prefix'),
            ]);

            $cacheResolver->addTag('liip_imagine.cache.resolver', [
                'resolver' => sprintf('%s_fs', $adapter),
            ]);

            $container->setDefinition(sprintf('ped_storage.imagine.cache.resolver.%s', $adapter), $cacheResolver);
        }
    }

    private function loadLoaders(string $adapter, ContainerBuilder $container)
    {
        $binaryLoader = new Definition(StreamLoader::class);
        $binaryLoader->setArgument('$wrapperPrefix', sprintf('pedstorage://%s_fs/', $adapter));
        $binaryLoader->addTag('liip_imagine.binary.loader', [
            'loader' => sprintf('loader_%s_data', $adapter),
        ]);

        $container->setDefinition(sprintf('ped_storage.imagine.binary.loader.%s', $adapter), $binaryLoader);
    }

    private function loadExternalResources(string $adapter, ContainerBuilder $container)
    {
        $filesystem = new Definition(Filesystem::class);

        if (Configuration::LOCAL_ADAPTER === $adapter) {
            if ($container->hasParameter('ped_storage.local.directory')) {
                $localAdapter = new Definition(Local::class);
                $localAdapter->setArgument('$directory', $container->getParameter('ped_storage.local.directory'));

                $filesystem->setArgument('$adapter', $localAdapter);
            }
        } elseif (Configuration::AMAZON_S3_ADAPTER === $adapter) {
            if (
                $container->hasDefinition('ped_storage.amazon_s3.client') &&
                $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
                $container->hasParameter('ped_storage.amazon_s3.directory')
            ) {
                $awsS3Adapter = new Definition(AwsS3::class);
                $awsS3Adapter->setArgument('$service', $container->getDefinition('ped_storage.amazon_s3.client'));
                $awsS3Adapter->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
                $awsS3Adapter->setArgument('$options', [
                    'create' => true,
                    'directory' => $container->getParameter('ped_storage.amazon_s3.directory'),
                ]);

                $filesystem->setArgument('$adapter', $awsS3Adapter);
            }
        }

        $container->setDefinition('ped_storage.filesystem', $filesystem);

        //$container->setParameter('liip_imagine.data_loader', sprintf('loader_%s_data', $adapter));
        //$container->setParameter('liip_imagine.cache', sprintf('%s_fs', $adapter));
    }
}
