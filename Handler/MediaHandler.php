<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 13/10/17
 * Time: 11:06.
 */

namespace PaneeDesign\StorageBundle\Handler;

use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PaneeDesign\StorageBundle\Entity\Media;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaHandler
{
    /**
     * @var Filesystem|ResolvableFilesystem
     */
    private $filesystem;

    /**
     * @var CacheManager
     */
    private $liipCacheManager;

    /**
     * Entity Id.
     *
     * @var int
     */
    private $id;

    /**
     * Entity type name (es. customer, admin, owner).
     *
     * @var string
     */
    private $type;

    /**
     * Key to retrive storage file.
     *
     * @var string
     */
    private $key;

    /**
     * File Type (es. image, video, document).
     *
     * @var string
     */
    private $fileType;

    /**
     * Set if the file will be stored with public access.
     *
     * @var bool
     */
    private $hasPublicAccess = false;

    /**
     * Media information object.
     *
     * @var Media\MediaInfo
     */
    private $mediaInfo = null;

    /**
     * Group folders using module id.
     *
     * @var bool
     */
    private $groupFolders = true;

    /**
     * @var string
     */
    private $localEndpoint;

    /**
     * Allowed mime types.
     *
     * @var array
     */
    private $allowedMimeTypes;

    /**
     * MediaHandler constructor.
     *
     * @param Filesystem   $filesystem
     * @param CacheManager $liipCacheManager
     * @param array        $allowedMimeTypes
     */
    public function __construct(Filesystem $filesystem, CacheManager $liipCacheManager, array $allowedMimeTypes = [])
    {
        $adapter = $filesystem->getAdapter();

        if ($adapter instanceof LocalAdapter) {
            $filesystem = new Filesystem($adapter);
        }

        $this->filesystem = $filesystem;
        $this->liipCacheManager = $liipCacheManager;

        $this->allowedMimeTypes = array_merge([
            'image/jpeg',
            'image/png',
            'image/gif',
        ], $allowedMimeTypes);
    }

    /**
     * @param AwsS3PublicUrlResolver|AwsS3PresignedUrlResolver|StaticUrlResolver $resolver
     */
    public function setAwsS3Resolver($resolver)
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            $decorated = new Filesystem($adapter);
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
     *
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
     *
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
     *
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
     * @param $fileType
     *
     * @return $this
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * @param $hasPublicAccess
     *
     * @return $this
     */
    public function setHasPublicAccess($hasPublicAccess)
    {
        $this->hasPublicAccess = $hasPublicAccess;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHasPublicAccess()
    {
        return $this->hasPublicAccess;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param object|string $crop
     * @param int           $rotation
     * @param int           $priority
     * @param string        $key
     * @param string        $ext
     * @param int           $size
     *
     * @return $this
     */
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

    /**
     * @param $rotation
     */
    public function setRotation($rotation)
    {
        if ($this->mediaInfo instanceof Media\CropInfo === false) {
            $this->mediaInfo = new Media\CropInfo();
        }

        $this->mediaInfo->setRotation($rotation);
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        if ($this->mediaInfo instanceof Media\CropInfo === false) {
            $this->mediaInfo = new Media\CropInfo();
        }

        $this->mediaInfo->setPriority($priority);

        return $this;
    }

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setDocumentInfo($page = 0)
    {
        if ($this->mediaInfo instanceof Media\DocumentInfo) {
            $this->mediaInfo->setPage($page);
        } else {
            $this->mediaInfo = new Media\DocumentInfo($page);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $ext
     *
     * @return $this
     */
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

    /**
     * @param int $size
     *
     * @return $this
     */
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
     *
     * @return $this
     */
    public function setGroupFolders($groupFolders)
    {
        $this->groupFolders = $groupFolders;

        return $this;
    }

    /**
     * @param array $allowedMimeTypes
     *
     * @return MediaHandler
     */
    public function setAllowedMimeTypes($allowedMimeTypes)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;

        return $this;
    }

    /**
     * @param string $allowedMimeType
     *
     * @return MediaHandler
     */
    public function addAllowedMimeType($allowedMimeType)
    {
        $this->allowedMimeTypes[] = $allowedMimeType;

        return $this;
    }

    /**
     * @param string $allowedMimeType
     */
    public function removeAllowedMimeType($allowedMimeType)
    {
        if ($index = array_search($allowedMimeType, $this->allowedMimeTypes) === false) {
            unset($this->allowedMimeTypes[$index]);
        }
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param UploadedFile $file
     *
     * @return MediaHandler
     */
    public function save(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();

        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($mimeType, $this->getAllowedMimeTypes())) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $mimeType));
        }

        $key = uniqid();
        $name = sprintf('%s.%s', $key, $file->guessExtension());
        $path = $this->getFullKey($name);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            $metadata = ['contentType' => $mimeType];

            if ($this->hasPublicAccess) {
                $metadata['ACL'] = 'public-read';
            }

            $adapter->setMetadata($path, $metadata);
        }

        $this->fixFileRotation($file);

        $adapter->write($path, file_get_contents($file->getPathname()));

        return $this;
    }

    /**
     * @param Media $source
     * @param Media $dest
     *
     * @return bool|int|string
     */
    public function copy(Media $source, Media $dest)
    {
        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        $content = $adapter->read($source->getFullKey());

        return $adapter->write($dest->getFullKey(), $content);
    }

    /**
     * @param Media $media
     */
    public function remove(Media $media)
    {
        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        // Remove main media
        @$adapter->delete($media->getFullKey());

        // Remove cached thumbinails
        $this->liipCacheManager->remove($media->getFullKey(), null);
    }

    /**
     * @deprecated
     *
     * @param string $key
     */
    public function removeThumbnails($key)
    {
        $fullKey = $this->getThumbnailFolder($key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();
        @$adapter->delete($fullKey);
    }

    /**
     * @param string $fullKey
     *
     * @return bool|string
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     */
    public function getFullUrl($fullKey)
    {
        $toReturn = false;

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof LocalAdapter) {
            if ($adapter->exists($fullKey)) {
                $toReturn = $this->localEndpoint . '/' . $fullKey;
            }
        } else {
            if ($this->filesystem->has($fullKey)) {
                $toReturn = $this->filesystem->resolve($fullKey);
            }
        }

        return $toReturn;
    }

    /**
     * @param string $key
     *
     * @return int
     */
    public function getSize($key)
    {
        $fullKey = $this->getFullKey($key);

        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        return $adapter->size($fullKey);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getFullKey($key)
    {
        $this->setKey($key);

        $parts = [];

        if ($this->fileType) {
            $parts[] = $this->fileType;
        }

        if ($this->type) {
            $parts[] = $this->type;
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

    /**
     * @param string $key
     *
     * @return string
     */
    private function getThumbnailFolder($key)
    {
        list($key) = explode('.', $key);

        $parts = [];

        if ($this->type) {
            $parts[] = $this->type;
        }

        if ($this->fileType) {
            $parts[] = 'thumbnail';
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

    /**
     * @param array  $parts
     * @param string $key
     * @param string $ext
     *
     * @return string
     */
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

    /**
     * @param int $id
     *
     * @return string
     */
    private function getSubPathById($id)
    {
        $numericPath = ceil($id / 100);
        $stringPath = str_replace(
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
            ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l'],
            strval($numericPath)
        );

        return $stringPath;
    }

    /**
     * Fix file rotation using exif data
     * The file is modified only if needed.
     *
     * @param UploadedFile $file
     */
    private function fixFileRotation(UploadedFile $file)
    {
        if ($file->guessExtension() == 'jpeg') {
            $toRotate = 0;

            $exif = @exif_read_data($file->getPathname());
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $toRotate = 90;
                        break;
                    case 3:
                        $toRotate = 180;
                        break;
                    case 6:
                        $toRotate = -90;
                        break;
                }
            }

            if ($toRotate != 0) {
                $image = @imagecreatefromjpeg($file->getPathname());

                if (!empty($image)) {
                    $image = imagerotate($image, $toRotate, 0);
                    imagejpeg($image, $file->getPathname(), 95);
                    imagedestroy($image);
                }
            }
        }
    }
}
