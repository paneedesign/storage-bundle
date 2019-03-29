<?php
/**
 * Created by PhpStorm.
 * User: Fabiano Roberto
 * Date: 25/10/17
 * Time: 12:34.
 */

namespace PaneeDesign\StorageBundle\Entity\Media;

class MediaInfo
{
    /**
     * @var string default key
     */
    protected $key;

    /**
     * @var string default ext
     */
    protected $ext;

    /**
     * @var int size of media
     */
    protected $size;

    /**
     * MediaInfo constructor.
     *
     * @param string $key
     * @param string $ext
     * @param int    $size
     */
    public function __construct($key = '', $ext = '', $size = 0)
    {
        $this->key = $key;
        $this->ext = $ext;
        $this->size = $size;
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setExt($key)
    {
        $this->ext = $key;

        return $this;
    }

    public function getExt()
    {
        return $this->ext;
    }

    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function toJSON()
    {
        return get_object_vars($this);
    }
}
