<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 13/10/17
 * Time: 11:06
 */

namespace PaneeDesign\StorageBundle\Handler;

use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
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
    const TYPE_VIDEO     = 'video';
    const TYPE_DOCUMENT  = 'document';
    const TYPE_THUMBNAIL = 'thumbnail';

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
     * Entity type name (es. customer, admin, owner)
     *
     * @var string
     */
    private $type;

    /**
     * Key to retrive storage file
     *
     * @var string
     */
    private $key;

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

    /**
     * @var string
     */
    private $localEndpoint;

    /**
     * Allowed mime types
     *
     * @var array
     */
    private $allowedMimeTypes;

    /**
     * MediaHandler constructor.
     *
     * @param Filesystem $filesystem
     * @param array $allowedMimeTypes
     */
    public function __construct(Filesystem $filesystem, array $allowedMimeTypes = [])
    {
        $adapter = $filesystem->getAdapter();

        if ($adapter instanceof LocalAdapter) {
            $filesystem = new Filesystem($adapter);
        }

        $this->filesystem = $filesystem;

        $this->allowedMimeTypes = array_merge([
            'image/jpeg',
            'image/png',
            'image/gif'
        ], $allowedMimeTypes);
    }

    /**
     * @param AwsS3PublicUrlResolver|AwsS3PresignedUrlResolver|StaticUrlResolver $resolver
     */
    public function setAwsS3Resolver($resolver)
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

    /**
     * @param string $localEndpoint
     */
    public function setLocalEndpoint($localEndpoint)
    {
        $this->localEndpoint = $localEndpoint;
    }

    /**
     * @return string
     */
    public function getLocalEndpoint()
    {
        return $this->localEndpoint;
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
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

    /**
     * @return string
     */
    public function getMediaType()
    {
        return $this->mediaType;
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
     * @return array
     */
    public function getMediaInfo()
    {
        return $this->mediaInfo->toJSON();
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

    public function setAllowedMimeTypes($allowedMimeTypes)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    public function addAllowedMimeType($allowedMimeType)
    {
        $this->allowedMimeTypes[] = $allowedMimeType;
    }

    public function removeAllowedMimeType($allowedMimeType)
    {
        if ($index = array_search($allowedMimeType, $this->allowedMimeTypes) === false) {
            unset($this->allowedMimeTypes[$index]);
        }
    }

    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param UploadedFile $file
     * @return MediaHandler
     */
    public function save(UploadedFile $file)
    {
        $mimeType = $file->getClientMimeType();

        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($mimeType, $this->getAllowedMimeTypes())) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }

        $key  = uniqid();
        $name = sprintf('%s.%s', $key, $file->getExtension());
        $path = $this->getFullKey($name);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            $adapter->setMetadata($path, ['contentType' => $mimeType]);
        }

        $adapter->write($path, file_get_contents($file->getPathname()));

        return $this;
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

    private function getFullKey($key)
    {
        $this->setKey($key);

        $parts = [];

        if ($this->type) {
            $parts[] = $this->type;
        }

        if ($this->mediaType) {
            $parts[] = $this->mediaType;
        }

        if ($this->id) {
            if ($this->groupFolders) {
                $parts[] = $this->getSubPathById($this->id);
            } else {
                $parts[] = $this->id;
            }
        }

        return $this->computeParts($parts, $key);
    }

    private function getThumbnailFolder($key)
    {
        list($key) = explode('.', $key);

        $parts = [];

        if ($this->type) {
            $parts[] = $this->type;
        }

        if ($this->mediaType) {
            $parts[] = self::TYPE_THUMBNAIL;
        }

        if ($this->id) {
            if ($this->groupFolders) {
                $parts[] = $this->getSubPathById($this->id);
            } else {
                $parts[] = $this->id;
            }
        }

        return $this->computeParts($parts, $key);
    }

    private function computeParts($parts, $key = null, $ext = '')
    {
        if ($ext === '') {
            $name = $key;
        } else {
            $name = sprintf('%s.%s', $key, $ext);
        }

        switch (count($parts)) {
            case 3:
                $toReturn = sprintf('%s/%s/%s/%s', $parts[0], $parts[1], $parts[2], $name);
                break;
            case 2:
                $toReturn = sprintf('%s/%s/%s', $parts[0], $parts[1], $name);
                break;
            case 1:
                $toReturn = sprintf('%s/%s', $parts[0], $name);
                break;
            default:
                $toReturn = sprintf('%s/%s/%s/%s', date('Y'), date('m'), date('d'), $name);
                break;
        }

        return $toReturn;
    }

    private function getSubPathById($id)
    {
        return ceil($id / 100);
    }
}
