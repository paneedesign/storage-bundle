<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 13/06/17
 * Time: 17:10.
 */

namespace PaneeDesign\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use PaneeDesign\StorageBundle\Entity\Media\MediaInfo;

/**
 * Class Media.
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
     * @var MediaFilter[]
     *
     * @ORM\OneToMany(
     *     targetEntity="PaneeDesign\StorageBundle\Entity\MediaFilter",
     *     mappedBy="image",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected $filters;

    /**
     * @ORM\Column(name="filename", type="string", length=40, nullable=false)
     */
    private $key;

    /**
     * @ORM\Column(name="path", type="string", nullable=false)
     */
    private $path;

    /**
     * @ORM\Column(name="type", type="enum_media_type", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(name="file_type", type="enum_file_type", nullable=true)
     */
    private $fileType;

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
    private $createdAt;

    /**
     * @ORM\Column(name="is_public", type="boolean", options={"default" = false}))
     */
    private $isPublic = false;

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->filters = new ArrayCollection();
    }

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
     * Set key.
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
     * Get key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set type.
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
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mediaInfo.
     *
     * @param MediaInfo $mediaInfo
     *
     * @return Media
     */
    public function setMediaInfo($mediaInfo)
    {
        $this->mediaInfo = $mediaInfo;

        return $this;
    }

    /**
     * Get mediaInfo.
     *
     * @return array
     */
    public function getMediaInfo()
    {
        return $this->mediaInfo;
    }

    /**
     * Set createdAt.
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
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return Media
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set path.
     *
     * @param int $path
     *
     * @return Media
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get FullKey.
     *
     * @return string
     */
    public function getFullKey()
    {
        return $this->path . $this->key;
    }

    /**
     * Set file type.
     *
     * @param string $fileType
     *
     * @return Media
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * Get file type.
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Add filter.
     *
     * @param MediaFilter $filter
     *
     * @return Media
     */
    public function addFilter(MediaFilter $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Add filter.
     *
     * @param string $filterName
     * @param $url
     *
     * @return Media
     */
    public function addFilterByName($filterName, $url)
    {
        if (false === $this->hasFilter($filterName)) {
            $filter = new MediaFilter();
            $filter->setImage($this);
            $filter->setName($filterName);
            $filter->setUrl($url);
            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * Check if media has a filter.
     *
     * @param string $filterName
     *
     * @return bool
     */
    public function hasFilter($filterName)
    {
        foreach ($this->filters as $filter) {
            if ($filter->getName() === $filterName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return image url for a given filter.
     *
     * @param string $filterName
     *
     * @return string
     */
    public function getUrl($filterName)
    {
        foreach ($this->filters as $filter) {
            if ($filter->getName() === $filterName) {
                return $filter->getUrl();
            }
        }

        return false;
    }

    /**
     * Remove filter.
     *
     * @param MediaFilter $filter
     */
    public function removeFilter(MediaFilter $filter): void
    {
        $this->filters->removeElement($filter);
    }

    /**
     * Get filters.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        foreach ($this->filters as $filter) {
            $this->removeFilter($filter);
        }
    }

    /**
     * Set if file is Public.
     *
     * @param string $isPublic
     *
     * @return Media
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get if file is Public.
     *
     * @return string
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }
}
