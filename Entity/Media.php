<?php
/**
 * Created by PhpStorm.
 * User: fabianoroberto
 * Date: 13/06/17
 * Time: 17:10
 */

namespace PaneeDesign\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PaneeDesign\StorageBundle\Entity\Media\MediaInfo;

/**
 * Class Media
 * @package PaneeDesign\StorageBundle\Entity
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="PaneeDesign\StorageBundle\Entity\Repository\MediaRepository")
 * @ORM\Table(name="media", uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"filename"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
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
     * @ORM\Column(name="filename", type="string", length=40, nullable=false)
     */
    private $key;

    /**
     * __var \SplFileInfo
     * TODO: ma a cosa serve??
     */
    // protected $file;

    /**
     * @ORM\Column(name="type", type="enum_media_type", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    private $size = 0;

    /**
     * @ORM\Column(name="media_info", type="json_array", nullable=true)
     */
    private $mediaInfo = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * Constructor
     */
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
     * Set key
     *
     * @param string $key
     *
     * @return Media
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Media
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mediaInfo
     *
     * @param array|MediaInfo $mediaInfo
     *
     * @return Media
     */
    public function setMediaInfo($mediaInfo)
    {
        if (!is_array($mediaInfo)) {
            $mediaInfo = get_object_vars($mediaInfo);
        }

        $this->setsize($mediaInfo['size']);

        unset($mediaInfo['size']);
        unset($mediaInfo['key']);
        unset($mediaInfo['ext']);

        if (!empty($mediaInfo)) {
            $this->mediaInfo = $mediaInfo;
        }

        return $this;
    }

    /**
     * Get mediaInfo
     *
     * @return array
     */
    public function getMediaInfo()
    {
        return $this->mediaInfo;
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
     * Set size
     *
     * @param int $size
     *
     * @return Media
     */
    public function setsize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return int
     */
    public function getsize()
    {
        return $this->size;
    }
}
