<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 13/06/17
 * Time: 17:10
 */

namespace PaneeDesign\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Media
 * @package AppBundle\Entity
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="PaneeDesign\StorageBundle\Entity\Repository\MediaRepository")

 */

/**
 * Class Media
 * @package PaneeDesign\StorageBundle\Entity
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="PaneeDesign\StorageBundle\Entity\Repository\MediaRepository")
 * @ORM\Table(name="media", uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"path"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="media_ext", type="string")
 * @ORM\DiscriminatorMap({"media" = "Media"})
 */
abstract class Media
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var \SplFileInfo
     */
    protected $file;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $path;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $crop;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Media
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Media
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get file
     *
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param \SplFileInfo $file
     * @return Media
     */
    public function setFile(\SplFileInfo $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Has file
     *
     * @return bool
     */
    public function hasFile()
    {
        return null !== $this->file;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Media
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Has path
     *
     * @return bool
     */
    public function hasPath()
    {
        return null !== $this->path;
    }

    /**
     * Set crop
     *
     * @param array $crop
     *
     * @return Media
     */
    public function setCrop($crop)
    {
        $this->crop = $crop;

        return $this;
    }

    /**
     * Get crop
     *
     * @return array
     */
    public function getCrop()
    {
        return $this->crop;
    }
}
