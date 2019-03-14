<?php
/**
 * User: Fabiano Roberto <fabiano@paneedesign.com>
 * Date: 2019-01-24
 * Time: 16.00.
 */

namespace PaneeDesign\StorageBundle\Controller;

use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Service\FilterService;
use PaneeDesign\StorageBundle\DBAL\EnumFileType;
use PaneeDesign\StorageBundle\Entity\Media;
use PaneeDesign\StorageBundle\Entity\Repository\MediaRepository;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
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
     * @var FilterService
     */
    protected $filterService;

    public function __construct(MediaHandler $uploader, FilterService $filterService)
    {
        $this->uploader = $uploader;
        $this->filterService = $filterService;
    }

    /**
     * @Route(
     *     "/image/{key}",
     *     requirements={"key"=".+"},
     *     name="ped_storage_image",
     *     options={"i18n": false}
     * )
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     * @throws \Exception
     */
    public function imageAction(Request $request, $key)
    {
        $filter = $request->get('filter');

        /* @var MediaRepository $repository */
        $repository = $this->get('doctrine')
                           ->getRepository(Media::class);

        /* @var Media $media */
        $media = $repository->findOneBy(['key' => $key]);

        if ($media === null) {
            throw $this->createNotFoundException('Mediakeynot found!');
        }

        if ($media->getFileType() !== EnumFileType::IMAGE) {
            throw new \Exception('File type not handled!');
        }

        $liipCacheManager = $this->get('liip_imagine.cache.manager');

        $url = $this->uploader->getFullUrl($media->getFullKey());

        if ($filter !== null) {
            $path = $media->getFullKey();
            if ($liipCacheManager->isStored($path, $filter)) {
                $url = $liipCacheManager->resolve($path, $filter);
            } else {
                //Update here image filter

                try {
                    $url = $this->filterService->getUrlOfFilteredImage($path, $filter);
                    //Cache generated with success
                } catch (NotLoadableException $e) {
                    throw new NotFoundHttpException(sprintf('Source image for path "%s" could not be found', $path));
                } catch (NonExistingFilterException $e) {
                    throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter));
                } catch (\RuntimeException $e) {
                    $errorTemplate = 'Unable to create image for path "%s" and filter "%s". Message was "%s"';
                    throw new \RuntimeException(sprintf($errorTemplate, $path, $filter, $e->getMessage()), 0, $e);
                }
            }

            //Maybe process $url to have the same domain for all pictures

            $media->addFilterByName($filter, $url);

            $em = $this->getDoctrine()->getManager();
            $em->persist($media);
            $em->flush();
        }

        return $this->redirect($url, 301);
    }

    /**
     * @Route(
     *     "/document/{key}",
     *     requirements={"key"=".+"},
     *     name="ped_storage_document",
     *     options={"i18n": false}
     * )
     *
     * @param string $key
     *
     * @return string
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     * @throws \Exception
     */
    public function documentAction($key)
    {
        /* @var MediaRepository $repository */
        $repository = $this->get('doctrine')
                           ->getRepository(Media::class);

        /* @var Media $media */
        $media = $repository->findOneBy(['key' => $key]);

        if ($media === null) {
            throw $this->createNotFoundException('Mediakeynot found!');
        }

        if ($media->getFileType() !== EnumFileType::DOCUMENT) {
            throw new \Exception('File type not handled!');
        }

        if (!$media->getIsPublic() && !$this->getUser()) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $resolver = $this->container->get('ped_storage.amazon_presigned_url_resolver');
        $this->uploader->setAwsS3Resolver($resolver);

        $url = $this->uploader->getFullUrl($media->getFullKey());

        return $this->redirect($url, 301);
    }

    /**
     * @Route(
     *     "/video/{key}",
     *     requirements={"key"=".+"},
     *     name="ped_storage_video",
     *     options={"i18n": false}
     * )
     *
     * @param string $key
     *
     * @return string
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     * @throws \Exception
     */
    public function videoAction($key)
    {
        /* @var MediaRepository $repository */
        $repository = $this->get('doctrine')
                           ->getRepository(Media::class);

        /* @var Media $media */
        $media = $repository->findOneBy(['key' => $key]);

        if ($media === null) {
            throw $this->createNotFoundException('Mediakeynot found!');
        }

        if ($media->getFileType() !== EnumFileType::VIDEO) {
            throw new \Exception('File type not handled!');
        }

        if (!$media->getIsPublic() && !$this->getUser()) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        $resolver = $this->container->get('ped_storage.amazon_presigned_url_resolver');
        $this->uploader->setAwsS3Resolver($resolver);

        $url = $this->uploader->getFullUrl($media->getFullKey());

        return $this->redirect($url, 301);
    }
}
