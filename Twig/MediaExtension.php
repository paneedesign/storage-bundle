<?php
/**
 * User: Fabiano Roberto <fabiano@paneedesign.com>
 * Date: 2019-01-24
 * Time: 16.00
 */

namespace PaneeDesign\StorageBundle\Twig;

use App\Entity\Media;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;

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
        $this->router    = $router;
    }

    public function getFunctions()
    {
        return [
            new \Twig_Function('image', array($this, 'image')),
            new \Twig_Function('document', array($this, 'document')),
            new \Twig_Function('video', array($this, 'video')),
        ];
    }

    /**
     * @param string $type
     * @param Media $image
     * @param string $filter
     * @param bool $fullUrl
     *
     * @return bool|string
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     */
    public function image($type, Media $image = null, $filter = null, $fullUrl = false)
    {
        $toReturn = null;

        if ($filter !== null) {
            if ($image->hasFilter($filter)) {
                $toReturn = $image->getUrl($filter);
            } else {
                $urlType  = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
                $toReturn = $this->router->generate('ped_storage_image', [
                    'key'    => $image->getKey(),
                    'filter' => $filter,
                ], $urlType);
            }
        } else {
            $service = $this->container->getParameter('ped_storage.uploader');

            /* @var MediaHandler $uploader */
            $uploader = $this->container->get($service);
            $toReturn = $uploader->getFullUrl($image->getFullKey());
        }

        return $toReturn;
    }

    /**
     * @param Media $document
     *
     * @param bool $fullUrl
     *
     * @return string
     */
    public function document(Media $document, $fullUrl = false)
    {
        $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->router->generate('ped_storage_document', ['key' => $document->getKey()], $urlType);
    }

    /**
     * @param Media $document
     *
     * @param bool $fullUrl
     *
     * @return string
     */
    public function video(Media $document, $fullUrl = false)
    {
        $urlType = $fullUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->router->generate('ped_storage_video', ['key' => $document->getKey()], $urlType);
    }
}
