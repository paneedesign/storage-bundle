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

Create a Controller with this route:

```php
/**
 * @Route(
 *     "/media/{key}",
 *     name="media",
 * )
 *
 * @param Request $request
 * @param string $key
 *
 * @return Response
 * @throws \Gaufrette\Extras\Resolvable\UnresolvableObjectException
 * @throws \Exception
 */
public function mediaAction(Request $request, $key)
{
    $filter = $request->get('filter');

    /* @var EntityRepository $repository */
    $repository = $this->get('doctrine')
        ->getRepository('AppBundle:Media');

    /* @var Media $media*/
    $media = $repository->findOneBy(['key' => $key]);

    if ($media === null) {
        throw $this->createNotFoundException('Mediakeynot found!');
    }

    if ($media->getFileType() !== EnumFileType::IMAGE) {
        throw new \Exception("File type not handled!");
    }

    $service = $this->container->getParameter('ped_storage.uploader');
    /* @var MediaHandler $uploader */
    $uploader = $this->container->get($service);

    $liipCacheManager = $this->get('liip_imagine.cache.manager');

    if ($filter !== null) {
        $path = $media->getFullKey();
        if ($liipCacheManager->isStored($path, $filter)) {
            $url = $liipCacheManager->resolve($path, $filter);
        } else {
            //Update here image filter
            $liipServiceFilter = $this->get('liip_imagine.service.filter');
            try {
                $url = $liipServiceFilter->getUrlOfFilteredImage($path, $filter);
                //Cache generated with success
            } catch (NotLoadableException $e) {
                throw new NotFoundHttpException(sprintf('Source image for path "%s" could not be found', $path));
            } catch (NonExistingFilterException $e) {
                throw new NotFoundHttpException(sprintf('Requested non-existing filter "%s"', $filter));
            } catch (RuntimeException $e) {
                $errorTemplate = 'Unable to create image for path "%s" and filter "%s". Message was "%s"';
                throw new \RuntimeException(sprintf($errorTemplate, $path, $filter, $e->getMessage()), 0, $e);
            }
        }

        $media->addFilterByName($filter, $url);
        $em = $this->getDoctrine()->getManager();
        $em->persist($media);
        $em->flush();
    } else {
        $url = $uploader->getFullUrl($media->getFullKey());
    }

    return $this->redirect($url, 301);
}
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


Generate a pre-signed url to open private document. TODO: test and update this shippet 
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
