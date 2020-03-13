<?php

declare(strict_types=1);
/**
 * User: Fabiano Roberto <fabiano.roberto@ped.technology>
 * Date: 13/06/17
 * Time: 17:10.
 */

namespace PaneeDesign\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PaneeDesign\StorageBundle\Entity\Traits\Timestampable\Timestampable;

/**
 * Class Media.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="PaneeDesign\StorageBundle\Repository\MediaRepository")
 * @ORM\Table(name="media", uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"filename"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"media": "Media"})
 */
abstract class Media
{
    use Timestampable;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    protected $key;

    /**
     * @ORM\Column(name="path", type="string", nullable=false)
     */
    protected $path;

    /**
     * @ORM\Column(name="type", type="enum_media_type", nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(name="file_type", type="enum_file_type", nullable=true)
     */
    protected $fileType;

    /**
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected $size = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_public", type="boolean", options={"default": false})
     */
    protected $isPublic = false;

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
     * @ORM\Embedded(class="PaneeDesign\StorageBundle\Entity\CropFilter")
     */
    protected $cropFilter;

    public function __construct()
    {
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
     */
    public function setKey($key): self
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
     * Set path.
     *
     * @param string $path
     */
    public function setPath($path): self
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
     * Set type.
     *
     * @param string $type
     */
    public function setType($type): self
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
     * Set file type.
     *
     * @param string $fileType
     */
    public function setFileType($fileType): self
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
     * Set size.
     *
     * @param int $size
     */
    public function setSize($size): self
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

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function addFilter(MediaFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Add filter.
     *
     * @param string $filterName
     * @param $url
     */
    public function addFilterByName($filterName, $url): self
    {
        if (!$this->hasFilter($filterName)) {
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
     */
    public function removeFilter(MediaFilter $filter): void
    {
        $this->filters->removeElement($filter);
    }

    /**
     * Get filters.
     */
    public function getFilters(): Collection
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

    public function setCropFilter(?CropFilter $cropFilter): self
    {
        $this->cropFilter = $cropFilter;

        return $this;
    }

    public function getCropFilter(): ?CropFilter
    {
        return $this->cropFilter;
    }
}
