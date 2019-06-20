<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 01/06/16
 * Time: 13:34.
 */

namespace PaneeDesign\StorageBundle\DBAL;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class EnumMediaType extends AbstractEnumType
{
    public const PROFILE = 'profile';
    public const COVER = 'cover';
    public const GALLERY = 'gallery';
    public const VIDEO = 'video';

    protected static $choices = [
        self::PROFILE => 'Profile',
        self::COVER => 'Cover',
        self::GALLERY => 'Gallery',
        self::VIDEO => 'Video',
    ];

    protected $name = 'enum_media_type';
}
