<?php
/**
 * Created by PhpStorm.
 * User: Fabiano Roberto
 * Date: 25/10/17
 * Time: 12:34.
 */

namespace PaneeDesign\StorageBundle\Entity\Media;

class CropInfo extends MediaInfo
{
    /**
     * @var int starting point of the x-axis
     */
    private $x1;

    /**
     * @var int starting point of the y-axis
     */
    private $y1;

    /**
     * @var int ending point of the x-axis
     */
    private $x2;

    /**
     * @var int ending point of the y-axis
     */
    private $y2;

    /**
     * @var int rotation degree
     */
    private $rotation;

    /**
     * @var int priority in gallery
     */
    private $priority;

    /**
     * CropInfo constructor.
     *
     * @param int    $x1
     * @param int    $y1
     * @param int    $x2
     * @param int    $y2
     * @param int    $rotation
     * @param int    $priority
     * @param string $key
     * @param string $ext
     */
    public function __construct($x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $rotation = 0, $priority = 0, $key = '', $ext = '')
    {
        $this->x1 = (int) $x1;
        $this->y1 = (int) $y1;
        $this->x2 = (int) $x2;
        $this->y2 = (int) $y2;

        $this->rotation = (int) $rotation;
        $this->priority = (int) $priority;

        parent::__construct($key, $ext);
    }

    public function setX1($x)
    {
        $this->x1 = (int) $x;

        return $this;
    }

    public function getX1()
    {
        return $this->x1;
    }

    public function setY1($y)
    {
        $this->y1 = (int) $y;

        return $this;
    }

    public function getY1()
    {
        return $this->y1;
    }

    public function setX2($x2)
    {
        $this->x2 = (int) $x2;

        return $this;
    }

    public function getX2()
    {
        return $this->x2;
    }

    public function setY2($y2)
    {
        $this->y2 = (int) $y2;

        return $this;
    }

    public function getY2()
    {
        return $this->y2;
    }

    public function setWidth($width)
    {
        $this->x2 = (int) $this->x1 + $width;

        return $this;
    }

    public function getWidth()
    {
        return $this->x2 - $this->x1;
    }

    public function setHeight($height)
    {
        $this->y2 = (int) $this->y1 + $height;

        return $this;
    }

    public function getHeight()
    {
        return $this->y2 - $this->y1;
    }

    public function setRotation($rotation)
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    public function getRotation()
    {
        return $this->rotation;
    }

    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function toJSON()
    {
        return get_object_vars($this);
    }
}
