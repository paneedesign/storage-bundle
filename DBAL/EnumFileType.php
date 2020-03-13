<?php

declare(strict_types=1);
/**
 * User: Luigi Cardamone <luigi.cardamone@ped.technology>
 * Date: 08/06/18
 * Time: 15.49.
 */

namespace PaneeDesign\StorageBundle\DBAL;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class EnumFileType extends AbstractEnumType
{
    public const IMAGE = 'image';
    public const AUDIO = 'audio';
    public const VIDEO = 'video';
    public const DOCUMENT = 'document';

    protected static $choices = [
        self::IMAGE => 'Image',
        self::AUDIO => 'Audio',
        self::VIDEO => 'Video',
        self::DOCUMENT => 'Document',
    ];

    protected $name = 'enum_file_type';
}
