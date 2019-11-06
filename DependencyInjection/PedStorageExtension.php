<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Aws\S3\S3Client;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
use Liip\ImagineBundle\Binary\Loader\StreamLoader;
use Liip\ImagineBundle\Imagine\Cache\Resolver\AwsS3Resolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class PedStorageExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                case 'knp_gaufrette':
                    $knpConfiguration = [
                        'stream_wrapper' => [
                            'protocol' => 'pedstorage',
                        ],
                    ];

                    if (Configuration::LOCAL_ADAPTER === $config['adapter']) {
                        $knpConfiguration['adapters'][sprintf('%s_adapter', $config['adapter'])] = [
                            'local' => [
                                'directory' => sprintf('%s/%s', $config['local']['web_root_dir'], $config['directory']),
                            ],
                        ];
                    } elseif (Configuration::AMAZON_S3_ADAPTER === $config['adapter']) {
                        $knpConfiguration['adapters'][sprintf('local_%s', $config['adapter'])] = [
                            'aws_s3' => [
                                'service_id' => 'ped_storage.amazon_s3.client',
                                'bucket_name' => $config['amazon_s3']['bucket_name'],
                                'options' => [
                                    'create' => true,
                                    'directory' => ['directory'],
                                ],
                            ],
                        ];
                    }

                    $knpConfiguration['filesystems'][sprintf('%s_fs', $config['adapter'])] = [
                        'adapter' => sprintf('%s_adapter', $config['adapter']),
                        'alias' => 'ped_storage.filesystem',
                    ];

                    $container->prependExtensionConfig($name, $knpConfiguration);

                    break;
                case 'liip_imagine':
                    $liipConfiguration = [
                        'data_loader' => sprintf('loader_%s_data', $config['adapter']),
                        'cache' => sprintf('%s_fs', $config['adapter']),
                    ];

                    $container->prependExtensionConfig($name, $liipConfiguration);

                    break;
            }
        }
    }

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
            $container->setParameter('ped_storage.directory', $config['directory']);
            $container->setParameter('ped_storage.thumbs_prefix', $config['thumbs_prefix']);

            switch ($config['adapter']) {
                case Configuration::LOCAL_ADAPTER:
                    $container->setParameter(
                        'ped_storage.directory',
                        sprintf('%s/%s', $config['local']['web_root_dir'], $config['directory'])
                    );

                    $this->loadParameters($config['adapter'], $config[Configuration::LOCAL_ADAPTER], $container);
                    $this->loadLocalResolvers($config['adapter'], $container);
                    $this->loadLoaders($config['adapter'], $container);

                    break;
                case Configuration::AMAZON_S3_ADAPTER:
                    $this->loadParameters($config['adapter'], $config[Configuration::AMAZON_S3_ADAPTER], $container);
                    $this->loadAmazonS3Client($container);
                    $this->loadAmazonS3Resolvers($config['adapter'], $container);
                    $this->loadLoaders($config['adapter'], $container);

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
        if (
            $container->hasParameter('ped_storage.thumbs_prefix') &&
            $container->hasParameter('ped_storage.local.web_root_dir')
        ) {
            $cacheResolver = new ChildDefinition('liip_imagine.cache.resolver.prototype.web_path');
            $cacheResolver->replaceArgument(2, $container->getParameter('ped_storage.local.web_root_dir'));
            $cacheResolver->replaceArgument(3, $container->getParameter('ped_storage.thumbs_prefix'));

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
            $container->hasParameter('ped_storage.directory')
        ) {
            $publicUrlResolver = new Definition(AwsS3PublicUrlResolver::class);
            $publicUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.amazon_s3.client'));
            $publicUrlResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
            $publicUrlResolver->setArgument('$baseDir', $container->getParameter('ped_storage.directory'));
            $publicUrlResolver->setPublic(true);

            $container->setDefinition('ped_storage.amazon_public_url_resolver', $publicUrlResolver);

            if ($container->hasDefinition('expire_at_datetime')) {
                $presignedUrlResolver = new Definition(AwsS3PresignedUrlResolver::class);
                $presignedUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.amazon_s3.client'));
                $presignedUrlResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
                $presignedUrlResolver->setArgument('$baseDir', $container->getParameter('ped_storage.directory'));
                $presignedUrlResolver->setArgument('$expiresAt', $container->getDefinition('expire_at_datetime'));
                $presignedUrlResolver->setPublic(true);

                $container->setDefinition('ped_storage.amazon_presigned_url_resolver', $presignedUrlResolver);
            }

            $staticUrlResolver = new Definition(StaticUrlResolver::class);
            $staticUrlResolver->setArgument('$prefix', $container->getParameter('ped_storage.directory'));
            $staticUrlResolver->setPublic(true);

            $container->setDefinition('ped_storage.amazon_static_url_resolver', $staticUrlResolver);
        }

        if (
            $container->hasDefinition('ped_storage.amazon_s3.client') &&
            $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
            $container->hasParameter('ped_storage.thumbs_prefix')
        ) {
            $cacheResolver = new Definition(AwsS3Resolver::class);
            $cacheResolver->setArgument('$storage', $container->getDefinition('ped_storage.amazon_s3.client'));
            $cacheResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
            $cacheResolver->addMethodCall('setCachePrefix', [
                $container->getParameter('ped_storage.thumbs_prefix'),
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
        $binaryLoader->addArgument(sprintf('pedstorage://%s_fs/', $adapter));
        $binaryLoader->addTag('liip_imagine.binary.loader', [
            'loader' => sprintf('loader_%s_data', $adapter),
        ]);

        $container->setDefinition(sprintf('ped_storage.imagine.binary.loader.%s', $adapter), $binaryLoader);
    }
}
