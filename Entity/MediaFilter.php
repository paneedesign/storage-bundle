<?php

declare(strict_types=1);
/**
 * User: Luigi Cardamone <luigi.cardamone@ped.technology>
 * Date: 05/06/18
 * Time: 9.58.
 */

namespace PaneeDesign\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MediaFilter.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class MediaFilter
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="PaneeDesign\StorageBundle\Entity\Media", inversedBy="filters")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id", nullable=false)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $url;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set filter.
     *
     * @param string $name
     *
     * @return MediaFilter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get filter.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set image.
     *
     * @param Media $image
     *
     * @return MediaFilter
     */
    public function setImage(Media $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return Media
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return MediaFilter
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
