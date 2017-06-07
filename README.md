Pane e Design - Storage Bundle
==============================

Users management for Symfony3 projects.

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

            new \Liip\ImagineBundle\LiipImagineBundle(),
            new \Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
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
// app/config/config.yml
//...
ped_storage:
    amazon_s3:
        key:         "%storage_amazon_s3_key%"
        secret:      "%storage_amazon_s3_secret%"
        region:      "%storage_amazon_s3_region%"
        base_url:    "%storage_amazon_s3_base_url%"
        bucket_name: "%storage_amazon_s3_bucket_name%"
        directory:   "%storage_amazon_s3_directory%"
    local:
        directory:   "%storage_local_directory%"
//...
liip_imagine:
    data_loader: stream.amazon_fs
    //data_loader: stream.local_fs
```