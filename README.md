Pane e Design - Storage Bundle
==============================

Storage management for Symfony3 projects.

Installation
============

Step 1: Download the Bundle
---------------------------

Pane&Design repository is private so, add to `composer.json` this `vcs`

```json
    "repositories" : [
        ...
        {
            "type" : "vcs",
            "url" : "git@bitbucket.org:paneedesign/storage-bundle.git"
        }
    ],
    ...
    "require": {
        ...
        "paneedesign/storage-bundle": "^4.0"   
    }
```

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

Copy parameters

```
// app/config/parameters.yml.dist
parameters:
    ...
    storage_amazon_s3_key:         ~
    storage_amazon_s3_secret:      ~
    storage_amazon_s3_region:      eu-west-1
    storage_amazon_s3_endpoint:    'https://s3.amazonaws.com'
    storage_amazon_s3_bucket_name: ~
    storage_amazon_s3_directory:   uploads
    storage_amazon_s3_expire_at:   +1 hour
    storage_local_directory:       "%kernel.root_dir%/../web/uploads"
    storage_local_endpoint:        /uploads
    storage_amazon_s3_thumbs_prefix: thumbs
    storage_local_thumbs_prefix: thumbs
    storage_adapter:               local
    #storage_adapter:              amazon
```

Add configuration:

```yml
//...
imports:
    - { resource: "@PedUserBundle/Resources/config/config.yml" }

//...

doctrine:
    dbal:
        types:
            enum_media_type: PaneeDesign\StorageBundle\DBAL\EnumMediaType
            enum_file_type: PaneeDesign\StorageBundle\DBAL\EnumFileType
            
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

and retrive full url by using:


```php
/**
 * Get full Image url from S3
 *
 * @param $path
 * @param $type
 * @return string
 */
protected function getAmazonImageUrl($key, $id, $type)
{
    //parameters.yml
    //storage_adapter: amazon
    
    $service  = $this->getParameter('ped_storage.uploader');
    $uploader = $this->get($service)
        ->setId($id)
        ->setType($type);
        
    // optionally set a mediaType (es. image, video, thumbnail, document)
    $uploader->setFileType('thumbnail');
      
    return $uploader->getFullUrl($key);
}
```

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
