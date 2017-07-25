<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 07/06/17
 * Time: 09:16
 */

namespace PaneeDesign\StorageBundle\Upload;

use Gaufrette\Adapter\AwsS3;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PhotoUploader
{
    private static $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Entity Id
     *
     * @var integer
     */
    private $id;

    /**
     * Entity Id
     *
     * @var string
     */
    private $type;

    /**
     * PhotoUploader constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function upload(UploadedFile $file)
    {
        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }

        $filename = $this->getFilename($file);

        /* @var AwsS3|Local $adapter */
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof AwsS3) {
            $adapter->setMetadata($filename, ['contentType' => $file->getClientMimeType()]);
        }

        $adapter->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }

    public function getFullUrl($key) {
        /* @var AwsS3|Local $adapter */
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof Local) {
            $content  = $adapter->read($key);
            $mimeType = $adapter->mimeType($key);

            $toReturn = "data:".$mimeType.";base64,".base64_encode($content);
        } else {
            $toReturn = $adapter->getUrl($key);
        }

        return $toReturn;
    }

    public function getResponse($key) {
        /* @var AwsS3|Local $adapter */
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof Local) {
            $content  = $adapter->read($key);
            $mimeType = $adapter->mimeType($key);

            //$toReturn = "data:".$mimeType.";base64,".base64_encode($content);

            $response = new Response($content, 200);
            $response->headers->set('Content-Type', $mimeType);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 7))); // 1 week
        } else {
            $response = new RedirectResponse($adapter->getUrl($key));
        }

        return $response;
    }

    private function getFilename(UploadedFile $file)
    {
        $part = [];

        if($this->type) {
            $part[] = $this->type;
        }

        if($this->id) {
            $part[] = $this->getSubPathById($this->id);
        }

        if(count($part) == 2) {
            $filename = sprintf('%s/%s/%s.%s', $part[0], $part[1], uniqid(), $file->getClientOriginalExtension());
        } else if(count($part) == 1) {
            $filename = sprintf('%s/%s.%s', $part[0], uniqid(), $file->getClientOriginalExtension());
        } else {
            $filename = sprintf('%s/%s/%s/%s.%s', date('Y'), date('m'), date('d'), uniqid(), $file->getClientOriginalExtension());
        }

        return $filename;
    }

    private function getSubPathById($id)
    {
        return ceil($id / 100);
    }
}