Pane e Design - Storage Bundle
==============================

A Symfony bundle that provide tools to handle media storage locally or on S3.

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require "paneedesign/storage-bundle"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new \Fresh\DoctrineEnumBundle\FreshDoctrineEnumBundle(),
            new \Liip\ImagineBundle\LiipImagineBundle(),
            new \Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new \PaneeDesign\DiscriminatorMapBundle\PedDiscriminatorMapBundle(),
            new \PaneeDesign\StorageBundle\PedStorageBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configurations
----------------------

Add to `.env`

```dotenv
###> paneedesign/storage-bundle ###
STORAGE_ADAPTER=local
STORAGE_LOCAL_DIRECTORY=uploads
STORAGE_LOCAL_ENDPOINT=uploads
STORAGE_LOCAL_THUMBS_PREFIX=thumbs
###< paneedesign/storage-bundle ###
```

or

```dotenv
###> paneedesign/storage-bundle ###
STORAGE_ADAPTER=amazon_s3
STORAGE_AMAZON_S3_KEY=key
STORAGE_AMAZON_S3_SECRET=secret
STORAGE_AMAZON_S3_REGION=eu-west-2
STORAGE_AMAZON_S3_ENDPOINT=https://s3.amazonaws.com
STORAGE_AMAZON_S3_BUCKET_NAME=ped-local
STORAGE_AMAZON_S3_DIRECTORY=uploads
STORAGE_AMAZON_S3_EXPIRE_AT="+1 hour"
STORAGE_AMAZON_S3_THUMBS_PREFIX=thumbs
###< paneedesign/storage-bundle ###
```

Copy under `config/packeges` following files: 

* `config/packeges/ped_storage.yaml`

and under `config/routes`:

* `config/routes/ped_storage.yaml`

Set into `config/packages/doctrine.yaml`

```yml
//...

doctrine:
    dbal:
        types:
            enum_media_type: PaneeDesign\StorageBundle\DBAL\EnumMediaType
            enum_file_type: PaneeDesign\StorageBundle\DBAL\EnumFileType
```            

and into `config/packages/ped_discriminator_map.yaml`

```yml
//...

ped_discriminator_map:
    maps:
        media:
            entity: PaneeDesign\StorageBundle\Entity\Media
            children:
                app_media: AppBundle\Entity\Media
        ...
```

Step 4: Use
-----------

You can store image or document and retrive full url of resource using this snippets:

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\Media;
use Gaufrette\Extras\Resolvable\UnresolvableObjectException;
use PaneeDesign\StorageBundle\DBAL\EnumFileType;
use PaneeDesign\StorageBundle\DBAL\EnumMediaType;
use PaneeDesign\StorageBundle\Entity\Media as PedMedia;
use PaneeDesign\StorageBundle\Handler\MediaHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class StorageHandler
{
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var MediaHandler
     */
    private $mediaHandler;

    /**
     * MediaManager constructor.
     *
     * @param MediaHandler    $mediaHandler
     * @param RouterInterface $router
     */
    public function __construct(MediaHandler $mediaHandler, RouterInterface $router)
    {
        $this->mediaHandler = $mediaHandler;
        $this->router = $router;
    }

    /**
     * @param PedMedia    $media
     * @param string|null $filter
     *
     * @return string
     */
    public function generateAbsoluteUri(PedMedia $media, ?string $filter = null)
    {
        $url = '';

        try {
            if (null !== $filter) {
                if ($media->hasFilter($filter)) {
                    $url = $media->getUrl($filter);
                } else {
                    $url = $this->router->generate('ped_storage_image', [
                        'key' => $media->getKey(),
                        'filter' => $filter,
                    ]);
                }
            } else {
                $url = $this->mediaHandler->getFullUrl($media->getFullKey());
            }
        } catch (UnresolvableObjectException $e) {
        } catch (\Exception $e) {
        }

        return $url ?: '';
    }

    /**
     * @param int           $entityId
     * @param string        $type
     * @param UploadedFile  $media
     * @param PedMedia|null $image
     * @param string|null   $mediaType
     *
     * @throws \Exception
     *
     * @return Media
     */
    public function storeImage(
        int $entityId,
        string $type,
        UploadedFile $media,
        ?PedMedia $image = null,
        ?string $mediaType = EnumMediaType::PROFILE
    ): Media {
        $hasPublicAccess = false;
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];

        $uploader = $this->getUploader($entityId, $type, EnumFileType::IMAGE, $hasPublicAccess, $allowedMimeTypes);

        if (null === $image) {
            $image = new Media();
            $image->setType($mediaType);
        } else {
            $image->clearFilters();
            $uploader->remove($image);
        }

        $uploader->save($media);

        $image = new Media();
        $image->setKey($uploader->getKey());
        $image->setPath($uploader->getFullKey(''));
        $image->setFileType($uploader->getFileType());
        $image->setSize($media->getSize());
        $image->setIsPublic($uploader->getHasPublicAccess());

        return $image;
    }

    /**
     * @param int           $entityId
     * @param string        $type
     * @param UploadedFile  $media
     * @param PedMedia|null $document
     * @param string|null   $mediaType
     *
     * @throws \Exception
     *
     * @return Media
     */
    public function storeDocument(
        int $entityId,
        string $type,
        UploadedFile $media,
        ?PedMedia $document = null,
        ?string $mediaType = EnumMediaType::DOCUMENT
    ): Media {
        $hasPublicAccess = true;
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
        ];

        $uploader = $this->getUploader($entityId, $type, EnumFileType::DOCUMENT, $hasPublicAccess, $allowedMimeTypes);

        if (null === $document) {
            $document = new Media();
            $document->setType($mediaType);
        } else {
            $document->clearFilters();
            $uploader->remove($document);
        }

        $uploader->save($media);

        $document = new Media();
        $document->setKey($uploader->getKey());
        $document->setPath($uploader->getFullKey(''));
        $document->setFileType($uploader->getFileType());
        $document->setSize($media->getSize());
        $document->setIsPublic($uploader->getHasPublicAccess());

        return $document;
    }

    /**
     * @param int    $entityId
     * @param string $type
     * @param string $fileType
     * @param array  $allowedMimeTypes
     * @param bool   $hasPublicAccess
     *
     * @return MediaHandler
     */
    private function getUploader(
        int $entityId,
        string $type,
        string $fileType,
        bool $hasPublicAccess,
        array $allowedMimeTypes = []
    ): MediaHandler {
        /* @var MediaHandler $uploader */
        $uploader = $this->mediaHandler
            ->setId($entityId)
            ->setType($type)
            ->setFileType($fileType)
            ->setAllowedMimeTypes($allowedMimeTypes)
            ->setHasPublicAccess($hasPublicAccess);

        return $uploader;
    }
}
```
