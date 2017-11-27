<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 13/10/17
 * Time: 11:06
 */

namespace PaneeDesign\StorageBundle\Handler;

use PaneeDesign\StorageBundle\Entity\Media;

use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaHandler
{
    const TYPE_IMAGE     = 'image';
    const TYPE_DOCUMENT  = 'document';
    const TYPE_THUMBNAIL = 'thumbnail';

    private static $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.oasis.opendocument.text',
    ];

    public static $extension = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'odt'  => 'application/vnd.oasis.opendocument.text',
    ];

    /**
     * @var Filesystem|ResolvableFilesystem
     */
    private $filesystem;

    /**
     * Entity Id
     *
     * @var integer
     */
    private $id;

    /**
     * Type name (es. hostess, steward, job)
     *
     * @var string
     */
    private $type;

    /**
     * Media Type (es. profile, gallery, document, thumbnail)
     *
     * @var string
     */
    private $mediaType;

    /**
     * Media information object
     *
     * @var Media\MediaInfo
     */
    private $mediaInfo = null;

    /**
     * Group folders using module id
     *
     * @var bool
     */
    private $groupFolders = true;


    private $localEndpoint;

    /**
     * MediaHandler constructor.
     *
     * @param Filesystem $filesystem
     * @param AwsS3PublicUrlResolver $resolver
     * @param string $localEndpoint
     */
    public function __construct(Filesystem $filesystem, $resolver = null, $localEndpoint = null)
    {
        $adapter = $filesystem->getAdapter();

        if ($adapter instanceof LocalAdapter) {
            $filesystem = new Filesystem($adapter);
        }

        $this->filesystem = $filesystem;
    }

    public function setAwsS3PublicUrlResolver(AwsS3PublicUrlResolver $resolver)
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            $decorated  = new Filesystem($adapter);
            $filesystem = new ResolvableFilesystem(
                $decorated,
                $resolver
            );

            $this->filesystem = $filesystem;
        }
    }

    public function setLocalEndpoint($localEndpoint)
    {
        $this->localEndpoint = $localEndpoint;
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

    /**
     * @param $mediaType
     * @return $this
     */
    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function setCropInfo($crop = null, $rotation = null, $priority = 0, $key = '', $ext = '', $size = 0)
    {
        if ($crop !== null) {
            if (is_object($crop)) {
                $cropInfo = $crop;
            } else {
                $cropInfo = json_decode($crop);
            }

            $this->mediaInfo = new Media\CropInfo($cropInfo->x, $cropInfo->y);
            $this->mediaInfo->setWidth($cropInfo->width);
            $this->mediaInfo->setHeight($cropInfo->height);
        }

        if ($rotation !== null) {
            $this->setRotation($rotation);
        }

        if ($priority > 0) {
            $this->setPriority($priority);
        }

        if ($key !== '' && $ext !== '') {
            $this->setName($key, $ext);
        }

        if ($size > 0) {
            $this->setSize($size);
        }

        return $this;
    }

    public function setRotation($rotation)
    {
        if ($this->mediaInfo instanceof Media\CropInfo === false) {
            $this->mediaInfo = new Media\CropInfo();
        }

        $this->mediaInfo->setRotation($rotation);
    }

    public function setPriority($priority)
    {
        if ($this->mediaInfo instanceof Media\CropInfo === false) {
            $this->mediaInfo = new Media\CropInfo();
        }

        $this->mediaInfo->setPriority($priority);

        return $this;
    }

    public function setDocumentInfo($page = 0)
    {
        if ($this->mediaInfo instanceof Media\DocumentInfo) {
            $this->mediaInfo->setPage($page);
        } else {
            $this->mediaInfo = new Media\DocumentInfo($page);
        }

        return $this;
    }

    public function setName($key, $ext)
    {
        if ($this->mediaInfo instanceof Media\MediaInfo) {
            $this->mediaInfo->setKey($key);
            $this->mediaInfo->setExt($ext);
        } else {
            $this->mediaInfo = new Media\MediaInfo($key, $ext);
        }

        return $this;
    }

    public function setSize($size)
    {
        if ($this->mediaInfo instanceof Media\MediaInfo === false) {
            $this->mediaInfo = new Media\MediaInfo();
        }

        $this->mediaInfo->setSize($size);

        return $this;
    }

    /**
     * @param $groupFolders
     * @return $this
     */
    public function setGroupFolders($groupFolders)
    {
        $this->groupFolders = $groupFolders;

        return $this;
    }

    public function save(UploadedFile $file, $key = null)
    {
        $mimeType = $file->getClientMimeType();

        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }

        $ext = array_search(
            $mimeType,
            self::$extension,
            true
        );

        $fileInfo = $this->getFileInfo($ext, $key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            $adapter->setMetadata($fileInfo['path'], ['contentType' => $mimeType]);
        }

        $adapter->write($fileInfo['path'], file_get_contents($file->getPathname()));

        return $fileInfo;
        //return $this->getMedia($fileInfo['name'], $fileType);
    }

    public function remove($key)
    {
        $fullKey = $this->getFullKey($key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();
        @$adapter->delete($fullKey);
    }

    public function removeThumbnails($key)
    {
        $fullKey = $this->getThumbnailFolder($key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();
        @$adapter->delete($fullKey);
    }

    public function getFullUrl($key)
    {
        $toReturn = false;

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        $fullKey = $this->getFullKey($key);

        if ($adapter instanceof LocalAdapter) {
            if ($adapter->exists($fullKey)) {
                $toReturn = $this->localEndpoint.'/'.$fullKey;
            }
        } else {
            if ($this->filesystem->has($fullKey)) {
                $toReturn = $this->filesystem->resolve($fullKey);
            }
        }

        return $toReturn;
    }

    public function getSize($key)
    {
        $fullKey = $this->getFullKey($key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        return $adapter->size($fullKey);
    }

    private function getFileInfo($ext, $key = null)
    {
        if ($key === null) {
            $key = uniqid();
        }

        $part = [];

        if ($this->type) {
            $part[] = $this->type;
        }

        if ($this->mediaType) {
            $part[] = $this->mediaType;
        }

        if ($this->id) {
            if ($this->groupFolders) {
                $part[] = $this->getSubPathById($this->id);
            } else {
                $part[] = $this->id;
            }
        }

        switch (count($part)) {
            case 3:
                $path = sprintf('%s/%s/%s/%s.%s', $part[0], $part[1], $part[2], $key, $ext);
                break;
            case 2:
                $path = sprintf('%s/%s/%s.%s', $part[0], $part[1], $key, $ext);
                break;
            case 1:
                $path = sprintf('%s/%s.%s', $part[0], $key, $ext);
                break;
            default:
                $path = sprintf('%s/%s/%s/%s.%s', date('Y'), date('m'), date('d'), $key, $ext);
                break;
        }

        return array(
            'name' => sprintf('%s.%s', $key, $ext),
            'path' => $path,
        );
    }

    private function getFullKey($key)
    {
        $part = [];

        if ($this->type) {
            $part[] = $this->type;
        }

        if ($this->mediaType) {
            $part[] = $this->mediaType;
        }

        if ($this->id) {
            if ($this->groupFolders) {
                $part[] = $this->getSubPathById($this->id);
            } else {
                $part[] = $this->id;
            }
        }

        switch (count($part)) {
            case 3:
                $filename = sprintf('%s/%s/%s/%s', $part[0], $part[1], $part[2], $key);
                break;
            case 2:
                $filename = sprintf('%s/%s/%s', $part[0], $part[1], $key);
                break;
            case 1:
                $filename = sprintf('%s/%s', $part[0], $key);
                break;
            default:
                $filename = sprintf('%s/%s/%s/%s', date('Y'), date('m'), date('d'), $key);
                break;
        }

        return $filename;
    }

    private function getThumbnailFolder($key)
    {
        list($key) = explode('.', $key);

        $part = [];

        if ($this->type) {
            $part[] = $this->type;
        }

        if ($this->mediaType) {
            $part[] = self::TYPE_THUMBNAIL;
        }

        if ($this->id) {
            if ($this->groupFolders) {
                $part[] = $this->getSubPathById($this->id);
            } else {
                $part[] = $this->id;
            }
        }

        switch (count($part)) {
            case 3:
                $filename = sprintf('%s/%s/%s/%s', $part[0], $part[1], $part[2], $key);
                break;
            case 2:
                $filename = sprintf('%s/%s/%s', $part[0], $part[1], $key);
                break;
            case 1:
                $filename = sprintf('%s/%s', $part[0], $key);
                break;
            default:
                $filename = sprintf('%s/%s/%s/%s', date('Y'), date('m'), date('d'), $key);
                break;
        }

        return $filename;
    }

    private function getSubPathById($id)
    {
        return ceil($id / 100);
    }

    /*private function getMedia($fileName, $fileType)
    {
        $media = new Media();
        $media->setFilename($fileName);
        $media->setMediaInfo($this->mediaInfo);
        $media->setType($fileType);

        return $media;
    }*/
}
