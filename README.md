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
STORAGE_ADAPTER=amazon
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

You can upload a picture using this snippets:

```php
/**
 * Upload Image
 *
 * @param Request $request
 * @param string $name Image field name
 * @param int $id Entity ID
 * @param string $type Entity Type
 * @return Media
 */
protected function uploadImageAction(Request $request, $name, $id, $type)
{  
    $image   = $request->files->get($name);
    $service = $this->getParameter('ped_storage.uploader');
    
    $uploader = $this->get($service)
        ->setId($id)
        ->setType($type)
        ->setFileType(EnumFileType::IMAGE) //image, video, document
        ->setGroupFolders(false);
    
    $uploader->save($image);
    
    //Save in DB
    $media = new Media();
    
    $media->setKey($uploader->getKey());
    $media->setPath($uploader->getFullKey(''));
    $media->setFileType($uploader->getFileType());
    
    $media->setType(EnumMediaType::GALLERY);
    $media->setSize($image->getSize());

    $em = $this->getDoctrine()->getManager();
    $em->persist($media);
    $em->flush();
}
```

and retrive full url by using this function that can also be a Twig Filter:


```php
/**
 * @param Media $image
 * @param string $filter
 *
 * @return bool|string
 * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
 */
public function getMediaUrl(Media $image, $filter = null)
{
    if ($filter !== null) {
        if ($image->hasFilter($filter)) {
            return $image->getUrl($filter);
        } else {
            return $this->router->generate(
                'media',
                [
                    'key'    => $image->getKey(),
                    'filter' => $filter,
                ]
            );
        }
    } else {
        $service = $this->container->getParameter('ped_storage.uploader');
        /* @var MediaHandler $uploader */
        $uploader = $this->container->get($service);
        $url = $uploader->getFullUrl($image->getFullKey());
        return $url;
    }
}
```


Generate a pre-signed url to open private document. TODO: test and update this snippet 
```php
/**
 * Get full Document private url from S3
 *
 * @param $path
 * @param $type
 * @return string
 */
protected function getAmazonDocumentUrl($key, $id, $type)
{
    //parameters.yml
    //storage_adapter: amazon
    
    $service  = $this->getParameter('ped_storage.uploader');
    $resolver = $this->get('ped_storage.amazon_presigned_url_resolver');
    $uploader = $this->get($service)
        ->setAwsS3Resolver($resolver)
        ->setId($id)
        ->setType($type);

    // optionally set a mediaType (es. image, video, thumbnail, document)
    $uploader->setFileType('document');
      
    return $uploader->getFullUrl($key);
}
```
