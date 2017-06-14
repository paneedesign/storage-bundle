Pane e Design - Storage Bundle
==============================

Users management for Symfony3 projects.

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
            "url" : "git@gitlab.com:paneedesign/symfony-bundles/storage-bundle.git"
        }
    ],
    ...
    "require": {
        ...
        "paneedesign/storage-bundle": "dev-master"   
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

            new \Liip\ImagineBundle\LiipImagineBundle(),
            new \Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new \Netpositive\DiscriminatorMapBundle\NetpositiveDiscriminatorMapBundle(),
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
parameters:
    ...
    storage_amazon_s3_key:         ~
    storage_amazon_s3_secret:      ~
    storage_amazon_s3_region:      eu-west-1
    storage_amazon_s3_base_url:    ~
    storage_amazon_s3_bucket_name: ~
    storage_amazon_s3_directory:   uploads
    storage_local_directory:       "%kernel.root_dir%/../web/uploads"
```

Add configuration:

```yml
//...
imports:
    - { resource: "@PedUserBundle/Resources/config/config.yml" }
...

netpositive_discriminator_map:
    discriminator_map:
        media:
            entity: PaneeDesign\StorageBundle\Entity\Media
            children:
                app_media: AppBundle\Entity\Media
        ...
    
liip_imagine:
    data_loader: stream.amazon_fs
    //data_loader: stream.local_fs
```


Step 4: Use
-----------

You can upload a file using this snippets:

* Amazon

```php
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
```

* Local

```php
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
```

and retrive full url by using:

* Amazon 

```php
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
```

* Local

```php
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
```