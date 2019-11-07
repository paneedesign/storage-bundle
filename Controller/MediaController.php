<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 2019-01-24
 * Time: 16.00.
 */

namespace PaneeDesign\StorageBundle\Controller;

use Gaufrette\Extras\Resolvable\UnresolvableObjectException;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Service\FilterService;
use PaneeDesign\StorageBundle\DBAL\EnumFileType;
use PaneeDesign\StorageBundle\Entity\Media;
use PaneeDesign\StorageBundle\Exception\StorageException;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
use PaneeDesign\StorageBundle\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class MediaController extends AbstractController
{
    /**
     * @var MediaHandler
     */
    protected $uploader;

    /**
     * @var MediaRepository
     */
    protected $repository;

    /**
     * @var FilterService
     */
    protected $filterService;

    public function __construct(
        MediaHandler $uploader,
        MediaRepository $repository,
        FilterService $filterService
    ) {
        $this->uploader = $uploader;
        $this->repository = $repository;
        $this->filterService = $filterService;
    }

    /**
     * @Route(
     *     "/image/{key}",
     *     requirements={"key" = ".+"},
     *     name="ped_storage_image",
     *     options={"i18n" = false}
     * )
     *
     * @param Request $request
     * @param string  $key
     *
     * @throws UnresolvableObjectException
     * @throws StorageException
     *
     * @return Response
     */
    public function getImage(Request $request, string $key)
    {
        $filter = $request->get('filter');
        $media = $this->getMediaByKey($key);

        if (EnumFileType::IMAGE !== $media->getFileType()) {
            throw new StorageException('File type not handled', 'INVALID_MEDIA_TYPE');
        }

        $url = $this->uploader->getFullUrl($media->getFullKey());

        if (null !== $filter) {
            $path = $media->getFullKey();
            $mediaFilterName = $filter;

            try {
                if (strpos($filter, 'crop_') > -1) {
                    $filterName = str_replace('crop_', '', $filter);

                    $runtimeFilters = [
                        'crop' => [
                            'start' => [$request->get('start-x'), $request->get('start-y')],
                            'size'  => [$request->get('width'), $request->get('height')],
                        ],
                    ];

                    $mediaFilterName = md5(serialize($runtimeFilters));

                    $url = $this->filterService->getUrlOfFilteredImageWithRuntimeFilters($path, $filterName, $runtimeFilters);
                } elseif (strpos($filter, 'rotate_') > -1) {
                    $filterName = str_replace('rotate_', '', $filter);

                    $runtimeFilters = [
                        'rotate' => [
                            'angle' => $request->get('angle'),
                        ],
                    ];

                    $mediaFilterName = md5(serialize($runtimeFilters));

                    $url = $this->filterService->getUrlOfFilteredImageWithRuntimeFilters($path, $filterName, $runtimeFilters);
                } else {
                    $url = $this->filterService->getUrlOfFilteredImage($path, $filter);
                }
                //
                //Cache generated with success
            } catch (NotLoadableException $e) {
                throw new NotFoundHttpException(sprintf('Source image for path "%s" could not be found', $path));
            } catch (NonExistingFilterException $e) {
                throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter));
            } catch (\RuntimeException $e) {
                $errorTemplate = 'Unable to create image for path "%s" and filter "%s". Message was "%s"';
                throw new \RuntimeException(sprintf($errorTemplate, $path, $filter, $e->getMessage()), 0, $e);
            }

            //Maybe process $url to have the same domain for all pictures
            $media->addFilterByName($mediaFilterName, $url);

            $em = $this->getDoctrine()->getManager();
            $em->persist($media);
            $em->flush();
        }

        return $this->redirect($url, 301);
    }

    /**
     * @Route(
     *     "/document/{key}",
     *     requirements={"key" = ".+"},
     *     name="ped_storage_document",
     *     options={"i18n" = false}
     * )
     *
     * @param string $key
     *
     * @throws StorageException
     * @throws UnresolvableObjectException
     *
     * @return Response
     */
    public function getDocument(string $key)
    {
        return $this->getMediaByKeyAndType($key, EnumFileType::DOCUMENT);
    }

    /**
     * @Route(
     *     "/video/{key}",
     *     requirements={"key" = ".+"},
     *     name="ped_storage_video",
     *     options={"i18n" = false}
     * )
     *
     * @param string $key
     *
     * @throws StorageException
     * @throws UnresolvableObjectException
     *
     * @return Response
     */
    public function getVideo(string $key)
    {
        return $this->getMediaByKeyAndType($key, EnumFileType::VIDEO);
    }

    /**
     * @Route(
     *     "/audio/{key}",
     *     requirements={"key" = ".+"},
     *     name="ped_storage_audio",
     *     options={"i18n" = false}
     * )
     *
     * @param string $key
     *
     * @throws StorageException
     * @throws UnresolvableObjectException
     *
     * @return Response
     */
    public function getAudio(string $key)
    {
        return $this->getMediaByKeyAndType($key, EnumFileType::AUDIO);
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws StorageException
     * @throws UnresolvableObjectException
     */
    protected function getMediaByKeyAndType(string $key, string $type)
    {
        $media = $this->getMediaByKey($key);

        if ($type !== $media->getFileType()) {
            throw new StorageException('File type not handled', 'INVALID_MEDIA_TYPE');
        }

        if (!$media->getIsPublic() && !$this->getUser()) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        if ($this->uploader->isInstanceOfAmazonS3()) {
            $resolver = $this->container->get('ped_storage.amazon_presigned_url_resolver');
            $this->uploader->setAwsS3Resolver($resolver);
        }

        $url = $this->uploader->getFullUrl($media->getFullKey());

        return $this->redirect($url, 301);
    }

    /**
     * @param string $key
     *
     * @return Media
     * @throws StorageException
     */
    private function getMediaByKey(string $key): Media
    {
        /* @var Media $media */
        $media = $this->repository->findOneBy(['key' => $key]);

        if (null === $media) {
            throw new StorageException('Media key not found', 'INVALID_MEDIA_KEY');
        }

        return $media;
    }
}
