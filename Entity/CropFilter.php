<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 06/11/19
 * Time: 22:00.
 */

namespace PaneeDesign\StorageBundle\Entity;

/**
 * @ORM\Embeddable
 */
final class CropFilter
{
    /**
     * Starting point of the x-axis.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $x1 = 0;

    /**
     * Starting point of the y-axis.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $y1 = 0;

    /**
     * Ending point of the x-axis.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $x2 = 0;

    /**
     * Ending point of the y-axis.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $y2 = 0;

    /**
     * Rotation degree.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rotation = 0;

    /**
     * CropInfo constructor.
     *
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param int $rotation
     */
    public function __construct(
        ?int $x1 = 0,
        ?int $y1 = 0,
        ?int $x2 = 0,
        ?int $y2 = 0,
        ?int $rotation = 0
    ) {
        $this->x1 = $x1;
        $this->y1 = $y1;
        $this->x2 = $x2;
        $this->y2 = $y2;
        $this->rotation = $rotation;
    }

    public function __toString()
    {
        return json_encode($this);
    }

    public function setX1(int $x1)
    {
        $this->x1 = $x1;

        return $this;
    }

    public function getX1(): ?int
    {
        return $this->x1;
    }

    public function setY1(int $y1)
    {
        $this->y1 = $y1;

        return $this;
    }

    public function getY1(): ?int
    {
        return $this->y1;
    }

    public function setX2(int $x2)
    {
        $this->x2 = $x2;

        return $this;
    }

    public function getX2(): ?int
    {
        return $this->x2;
    }

    public function setY2(int $y2)
    {
        $this->y2 = $y2;

        return $this;
    }

    public function getY2(): ?int
    {
        return $this->y2;
    }

    public function setWidth(int $width)
    {
        $this->x2 = $this->x1 + $width;

        return $this;
    }

    public function getWidth(): ?int
    {
        $width = 0;

        if ($this->x2 > $this->x1) {
            $width = $this->x2 - $this->x1;
        }

        return $width;
    }

    public function setHeight(int $height)
    {
        $this->y2 = $this->y1 + $height;

        return $this;
    }

    public function getHeight(): ?int
    {
        $height = 0;

        if ($this->y2 > $this->y1) {
            $height = $this->y2 - $this->y1;
        }

        return $height;
    }

    public function setRotation(int $rotation)
    {
        $this->rotation = $rotation;

        return $this;
    }

    public function getRotation(): ?int
    {
        return $this->rotation;
    }
}
