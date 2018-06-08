<?php
/**
 * Created by PhpStorm.
 * User: luigi
 * Date: 08/06/18
 * Time: 15.49
 */

namespace PaneeDesign\StorageBundle\DBAL;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class EnumFileType extends AbstractEnumType
{
    const IMAGE    = 'image';
    const VIDEO    = 'video';
    const DOCUMENT = 'document';

    protected static $choices = [
        self::IMAGE    => 'Image',
        self::VIDEO    => 'Video',
        self::DOCUMENT => 'Document',
    ];

    protected $name = 'enum_file_type';
}