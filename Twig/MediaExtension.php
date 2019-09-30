<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 2019-01-24
 * Time: 16.00.
 */

namespace PaneeDesign\StorageBundle\Twig;

use PaneeDesign\StorageBundle\Entity\Media;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $router;

    public function __construct(ContainerInterface $container, RouterInterface $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('image', [$this, 'image']),
            new TwigFunction('document', [$this, 'document']),
            new TwigFunction('video', [$this, 'video']),
            new TwigFunction('audio', [$this, 'audio']),
        ];
    }

    /**
     * @param Media  $image
     * @param string $filter
     * @param bool   $fullUrl
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     *
     * @return bool|string
     */
    public function image(Media $image, ?string $filter = null, ?bool $fullUrl = false): string
    {
        if (null !== $filter) {
            if ($image->hasFilter($filter)) {
                return $image->getUrl($filter);
            }

            $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

            return $this->router->generate('ped_storage_image', [
                'key' => $image->getKey(),
                'filter' => $filter,
            ], $urlType);
        }

        $service = $this->container->getParameter('ped_storage.uploader');

        /* @var MediaHandler $uploader */
        $uploader = $this->container->get($service);

        return $uploader->getFullUrl($image->getFullKey());
    }

    /**
     * @param Media $document
     * @param bool  $fullUrl
     *
     * @return string
     */
    public function document(Media $document, ?bool $fullUrl = false): string
    {
        $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->router->generate('ped_storage_document', ['key' => $document->getKey()], $urlType);
    }

    /**
     * @param Media $video
     * @param bool  $fullUrl
     *
     * @return string
     */
    public function video(Media $video, ?bool $fullUrl = false): string
    {
        $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->router->generate('ped_storage_video', ['key' => $video->getKey()], $urlType);
    }

    /**
     * @param Media $audio
     * @param bool  $fullUrl
     *
     * @return string
     */
    public function audio(Media $audio, ?bool $fullUrl = false): string
    {
        $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->router->generate('ped_storage_audio', ['key' => $audio->getKey()], $urlType);
    }
}
