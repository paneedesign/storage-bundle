<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 01/06/16
 * Time: 13:34.
 */

namespace PaneeDesign\StorageBundle\DBAL;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class EnumMediaType extends AbstractEnumType
{
    const PROFILE = 'profile';
    const COVER = 'cover';
    const GALLERY = 'gallery';
    const VIDEO = 'video';

    protected static $choices = [
        self::PROFILE => 'Profile',
        self::COVER => 'Cover',
        self::GALLERY => 'Gallery',
        self::VIDEO => 'Video',
    ];

    protected $name = 'enum_media_type';
}
