<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 13/10/17
 * Time: 11:06.
 */

namespace PaneeDesign\StorageBundle\Handler;

use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PresignedUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Gaufrette\Extras\Resolvable\Resolver\StaticUrlResolver;
use Gaufrette\Filesystem;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PaneeDesign\StorageBundle\Entity\Media;
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
     * @param AwsS3PresignedUrlResolver|AwsS3PublicUrlResolver|StaticUrlResolver $resolver
     */
    public function setAwsS3Resolver($resolver): void
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
    public function setLocalEndpoint($localEndpoint): void
    {
        $this->localEndpoint = $localEndpoint;
    }

    /**
     * @return string
     */
    public function getLocalEndpoint(): string
    {
        return $this->localEndpoint;
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param $key
     *
     * @return $this
     */
    public function setKey($key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param $fileType
     *
     * @return $this
     */
    public function setFileType($fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * @param $hasPublicAccess
     *
     * @return $this
     */
    public function setHasPublicAccess($hasPublicAccess): self
    {
        $this->hasPublicAccess = $hasPublicAccess;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHasPublicAccess(): bool
    {
        return $this->hasPublicAccess;
    }

    /**
     * @return string
     */
    public function getFileType(): string
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
    public function setCropInfo($crop = null, $rotation = null, $priority = 0, $key = '', $ext = '', $size = 0): self
    {
        if (null !== $crop) {
            $cropInfo = $crop;

            if (false === \is_object($crop)) {
                $cropInfo = json_decode($crop);
            }

            $this->mediaInfo = new Media\CropInfo($cropInfo->x, $cropInfo->y);
            $this->mediaInfo->setWidth($cropInfo->width);
            $this->mediaInfo->setHeight($cropInfo->height);
        }

        if (null !== $rotation) {
            $this->setRotation($rotation);
        }

        if ($priority > 0) {
            $this->setPriority($priority);
        }

        if ('' !== $key && '' !== $ext) {
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
    public function setRotation($rotation): void
    {
        if (false === $this->mediaInfo instanceof Media\CropInfo) {
            $this->mediaInfo = new Media\CropInfo();
        }

        $this->mediaInfo->setRotation($rotation);
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority): self
    {
        if (false === $this->mediaInfo instanceof Media\CropInfo) {
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
    public function setDocumentInfo($page = 0): self
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
    public function setName($key, $ext): self
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
    public function setSize($size): self
    {
        if (false === $this->mediaInfo instanceof Media\MediaInfo) {
            $this->mediaInfo = new Media\MediaInfo();
        }

        $this->mediaInfo->setSize($size);

        return $this;
    }

    /**
     * @return array
     */
    public function getMediaInfo(): ?array
    {
        return $this->mediaInfo->toJSON();
    }

    /**
     * @param bool $groupFolders
     *
     * @return $this
     */
    public function setGroupFolders(bool $groupFolders): self
    {
        $this->groupFolders = $groupFolders;

        return $this;
    }

    /**
     * @param array $allowedMimeTypes
     *
     * @return MediaHandler
     */
    public function setAllowedMimeTypes(array $allowedMimeTypes): self
    {
        $this->allowedMimeTypes = $allowedMimeTypes;

        return $this;
    }

    /**
     * @param string $allowedMimeType
     *
     * @return MediaHandler
     */
    public function addAllowedMimeType(string $allowedMimeType): self
    {
        $this->allowedMimeTypes[] = $allowedMimeType;

        return $this;
    }

    /**
     * @param string $allowedMimeType
     */
    public function removeAllowedMimeType(string $allowedMimeType): void
    {
        if ($index = false === array_search($allowedMimeType, $this->allowedMimeTypes)) {
            unset($this->allowedMimeTypes[$index]);
        }
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param UploadedFile $file
     *
     * @return MediaHandler
     */
    public function save(UploadedFile $file): self
    {
        $mimeType = $file->getMimeType();

        // Check if the file's mime type is in the list of allowed mime types.
        if (false === \in_array($mimeType, $this->getAllowedMimeTypes())) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $mimeType));
        }

        $ext = $file->guessExtension();

        if ($ext === null) {
            $ext = $file->getClientOriginalExtension();
        }

        $key = uniqid();
        $name = sprintf('%s.%s', $key, $ext);
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
    public function remove(Media $media): void
    {
        /* @var AwsS3Adapter|LocalAdapter $adapter */
        $adapter = $this->filesystem->getAdapter();

        // Remove main media
        @$adapter->delete($media->getFullKey());

        // Remove cached thumbinails
        $this->liipCacheManager->remove($media->getFullKey(), null);
    }

    /**
     * @param string $fullKey
     *
     * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
     *
     * @return bool|string
     */
    public function getFullUrl(string $fullKey)
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
    public function getSize(string $key): int
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
    public function getFullKey(?string $key = null): string
    {
        if ($key) {
            $this->setKey($key);
        }

        $parts = [];

        if ($this->fileType) {
            $parts[] = $this->fileType;
        }

        if ($this->type) {
            $parts[] = $this->type;
        }

        if ($this->id) {
            $path = $this->id;

            if ($this->groupFolders) {
                $path = $this->getSubPathById($this->id);
            }

            $parts[] = $path;
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
    private function computeParts(array $parts, ?string $key = null, ?string $ext = null): string
    {
        $name = $key;

        if (null !== $ext) {
            $name = sprintf('%s.%s', $key, $ext);
        }

        switch (\count($parts)) {
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
    private function getSubPathById(int $id): string
    {
        $numericPath = ceil($id / 100);
        $stringPath = str_replace(
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
            ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l'],
            (string) $numericPath
        );

        return $stringPath;
    }

    /**
     * Fix file rotation using exif data
     * The file is modified only if needed.
     *
     * @param UploadedFile $file
     */
    private function fixFileRotation(UploadedFile $file): void
    {
        if ('jpeg' == $file->guessExtension()) {
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

            if ($toRotate) {
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
