<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 07/06/17
 * Time: 09:16
 */

namespace PaneeDesign\StorageBundle\Upload;

use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoUploader
{
    private static $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function upload(UploadedFile $file)
    {
        // Check if the file's mime type is in the list of allowed mime types.
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }

        // Generate a unique filename based on the date and add file extension of the uploaded file
        $filename = sprintf('%s/%s/%s/%s.%s', date('Y'), date('m'), date('d'), uniqid(), $file->getClientOriginalExtension());

        /* @var AwsS3 $adapter */
        $adapter = $this->filesystem->getAdapter();
        $adapter->setMetadata($filename, ['contentType' => $file->getClientMimeType()]);
        $adapter->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }
}