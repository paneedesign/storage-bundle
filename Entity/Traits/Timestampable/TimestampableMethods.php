<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\Entity\Traits\Timestampable;

use Doctrine\ORM\Mapping as ORM;

/**
 * Timestampable trait.
 *
 * Should be used inside entity, that needs to be timestamped.
 *
 * @ORM\HasLifecycleCallbacks
 */
trait TimestampableMethods
{
    /**
     * Returns createdAt value.
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onCreate()
    {
        $now = new \DateTime();

        $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
    }

    /**
     * @ORM\PreUpdate
     */
    public function onUpdate()
    {
        $now = new \DateTime();
        $this->setUpdatedAt($now);
    }
}
