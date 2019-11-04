<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\DependencyInjection;

use Aws\S3\S3Client;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
use Liip\ImagineBundle\Binary\Loader\StreamLoader;
use Liip\ImagineBundle\Imagine\Cache\Resolver\AwsS3Resolver;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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

        $container->setParameter('ped_storage.adapter', $config['adapter']);
        $container->setParameter('knp_gaufrette.stream_wrapper.protocol', 'pedstorage');

        switch ($config['adapter']) {
            case 'local':
                if (true === \array_key_exists('local', $config)) {
                    $container->setParameter('ped_storage.local.endpoint', $config['local']['endpoint']);
                    $container->setParameter('ped_storage.local.directory', $config['local']['directory']);
                    $container->setParameter('ped_storage.local.thumbs_prefix', $config['local']['thumbs_prefix']);
                }

                if ($container->hasParameter('ped_storage.local.thumbs_prefix')) {
                    $cacheResolver = new ChildDefinition('liip_imagine.cache.resolver.prototype.web_path');
                    $cacheResolver->setArgument('$cachePrefix', $container->getParameter('ped_storage.local.thumbs_prefix'));

                    $cacheResolver->addTag('liip_imagine.cache.resolver', [
                        'resolver' => 'local_fs',
                    ]);

                    $container->setDefinition('ped_storage.imagine.cache.resolver.local', $cacheResolver);
                }

                $binaryLoader = new Definition(StreamLoader::class);
                $binaryLoader->setArgument('$wrapperPrefix', 'pedstorage://local_fs/');
                $binaryLoader->addTag('liip_imagine.binary.loader', [
                    'loader' => 'loader_local_data',
                ]);

                $container->setDefinition('ped_storage.imagine.binary.loader.local', $binaryLoader);

                if ($container->hasParameter('ped_storage.local.directory')) {
                    $localAdapter = [
                        'local_adapter' => [
                            'local' => [
                                'directory' => $container->getParameter('ped_storage.local.directory'),
                            ],
                        ],
                    ];

                    $localFs = [
                        'local_fs' => [
                            'adapter' => 'local_adapter',
                            'alias' => 'local_fs',
                        ],
                    ];

                    $container->setParameter('knp_gaufrette.adapters', $localAdapter);
                    $container->setParameter('knp_gaufrette.filesystems', $localFs);

                    $container->setParameter('liip_imagine.data_loader', 'loader_local_data');
                    $container->setParameter('liip_imagine.cache', 'local_fs');
                }

                if ($container->hasDefinition('ped_storage.local.endpoint')) {
                    $handler = new Definition(MediaHandler::class);
                    $handler->setArgument('$filesystem', $container->getDefinition('local_fs'));
                    $handler->setArgument('$liipCacheManager', $container->getDefinition('liip_imagine.cache.manager'));
                    $handler->addMethodCall('setLocalEndpoint', [
                        $container->getDefinition('ped_storage.local.endpoint'),
                    ]);

                    $handler->setPublic(true);

                    $container->setDefinition('ped_storage.uploader', $handler);
                }

                break;
            case 'amazon':
                if (true === \array_key_exists('amazon_s3', $config)) {
                    $container->setParameter('ped_storage.amazon_s3.key', $config['amazon_s3']['key']);
                    $container->setParameter('ped_storage.amazon_s3.secret', $config['amazon_s3']['secret']);
                    $container->setParameter('ped_storage.amazon_s3.region', $config['amazon_s3']['region']);
                    $container->setParameter('ped_storage.amazon_s3.endpoint', $config['amazon_s3']['endpoint']);
                    $container->setParameter('ped_storage.amazon_s3.bucket_name', $config['amazon_s3']['bucket_name']);
                    $container->setParameter('ped_storage.amazon_s3.directory', $config['amazon_s3']['directory']);
                    $container->setParameter('ped_storage.amazon_s3.expire_at', $config['amazon_s3']['expire_at']);
                    $container->setParameter('ped_storage.amazon_s3.thumbs_prefix', $config['amazon_s3']['thumbs_prefix']);
                }

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

                    $container->setDefinition('ped_storage.aws_s3.client', $s3Client);
                }

                if (
                    $container->hasDefinition('ped_storage.aws_s3.client') &&
                    $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
                    $container->hasParameter('ped_storage.amazon_s3.directory')
                ) {
                    $publicUrlResolver = new Definition(AwsS3PublicUrlResolver::class);
                    $publicUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.aws_s3.client'));
                    $publicUrlResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
                    $publicUrlResolver->setArgument('$baseDir', $container->getParameter('ped_storage.amazon_s3.directory'));

                    $container->setDefinition('ped_storage.amazon_public_url_resolver', $publicUrlResolver);

                    if ($container->hasDefinition('expire_at_datetime')) {
                        $presignedUrlResolver = new Definition(AwsS3PresignedUrlResolver::class);
                        $presignedUrlResolver->setArgument('$service', $container->getDefinition('ped_storage.aws_s3.client'));
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
                    $container->hasDefinition('ped_storage.aws_s3.client') &&
                    $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
                    $container->hasParameter('ped_storage.amazon_s3.thumbs_prefix')
                ) {
                    $cacheResolver = new Definition(AwsS3Resolver::class);
                    $cacheResolver->setArgument('$storage', $container->getDefinition('ped_storage.aws_s3.client'));
                    $cacheResolver->setArgument('$bucket', $container->getParameter('ped_storage.amazon_s3.bucket_name'));
                    $cacheResolver->addMethodCall('setCachePrefix', [
                        $container->getParameter('ped_storage.amazon_s3.thumbs_prefix'),
                    ]);

                    $cacheResolver->addTag('liip_imagine.cache.resolver', [
                        'resolver' => 'amazon_fs',
                    ]);

                    $container->setDefinition('ped_storage.imagine.cache.resolver.aws_s3', $cacheResolver);
                }

                $binaryLoader = new Definition(StreamLoader::class);
                $binaryLoader->setArgument('$wrapperPrefix', 'pedstorage://amazon_fs/');
                $binaryLoader->addTag('liip_imagine.binary.loader', [
                    'loader' => 'loader_amazon_data',
                ]);

                $container->setDefinition('ped_storage.imagine.binary.loader.aws_s3', $binaryLoader);

                if (
                    $container->hasDefinition('ped_storage.aws_s3.client') &&
                    $container->hasParameter('ped_storage.amazon_s3.bucket_name') &&
                    $container->hasParameter('ped_storage.amazon_s3.directory')
                ) {
                    $amazonS3Adapter = [
                        'amazon_s3_adapter' => [
                            'aws_s3' => [
                                'service_id' => $container->getDefinition('ped_storage.aws_s3.client'),
                                'bucket_name' => $container->getParameter('ped_storage.amazon_s3.bucket_name'),
                                'options' => [
                                    'create' => true,
                                    'directory' => $container->getParameter('ped_storage.amazon_s3.directory'),
                                ],
                            ],
                        ],
                    ];

                    $amazonFs = [
                        'amazon_fs' => [
                            'adapter' => 'amazon_s3_adapter',
                            'alias' => 'amazon_fs',
                        ],
                    ];

                    $container->setParameter('knp_gaufrette.adapters', $amazonS3Adapter);
                    $container->setParameter('knp_gaufrette.filesystems', $amazonFs);

                    $container->setParameter('liip_imagine.data_loader', 'loader_amazon_data');
                    $container->setParameter('liip_imagine.cache', 'amazon_fs');
                }

                if ($container->hasDefinition('ped_storage.amazon_public_url_resolver')) {
                    $handler = new Definition(MediaHandler::class);
                    $handler->setArgument('$filesystem', $container->getDefinition('amazon_fs'));
                    $handler->setArgument('$liipCacheManager', $container->getDefinition('liip_imagine.cache.manager'));
                    $handler->addMethodCall('setAwsS3Resolver', [
                        $container->getDefinition('ped_storage.amazon_public_url_resolver'),
                    ]);

                    $handler->setPublic(true);

                    $container->setDefinition('ped_storage.uploader', $handler);
                }

                break;
        }
    }
}
