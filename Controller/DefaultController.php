<?php

namespace PaneeDesign\StorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function indexAction()
    {
        return $this->render('PedStorageBundle:Default:index.html.twig');
    }

    /**
     * Upload Image to S3
     *
     * @param Request $request
     * @param string $name Image field name
     * @param int $id Entity ID
     * @param string $type Entity Type
     * @return string
     */
    protected function amazonUploadImageAction(Request $request, $name, $id, $type)
    {
        $image    = $request->files->get($name);
        $uploader = $this->get('ped_storage.amazon_photo_uploader')
            ->setId($id)
            ->setType($type);

        return $uploader->upload($image);
    }

    /**
     * Upload Image to local
     *
     * @param Request $request
     * @param string $name Image field name
     * @param int $id Entity ID
     * @param string $type Entity Type
     * @return string
     */
    protected function localUploadImage(Request $request, $name, $id, $type)
    {
        $image    = $request->files->get($name);
        $uploader = $this->get('ped_storage.local_photo_uploader')
            ->setId($id)
            ->setType($type);

        return $uploader->upload($image);
    }

    /**
     * Get full Image url from S3
     *
     * @param $path
     * @param $type
     * @return string
     */
    protected function getAmazonImageUrl($path, $id, $type)
    {
        $uploader = $this->get('ped_storage.amazon_photo_uploader')
            ->setId($id)
            ->setType($type);

        return $uploader->getFullUrl($path);
    }

    /**
     * Get full Image url from local
     *
     * @param $path
     * @param $type
     * @return string
     */
    protected function getLocalImageUrl($path, $id, $type)
    {
        $uploader = $this->get('ped_storage.local_photo_uploader')
            ->setId($id)
            ->setType($type);

        return $uploader->getFullUrl($path);
    }
}
