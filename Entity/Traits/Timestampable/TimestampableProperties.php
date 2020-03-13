<?php

declare(strict_types=1);

namespace PaneeDesign\StorageBundle\Entity\Traits\Timestampable;

use Doctrine\ORM\Mapping as ORM;

/**
 * Timestampable trait.
 *
 * Should be used inside entity, that needs to be timestamped.
 */
trait TimestampableProperties
{
    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default": "CURRENT_TIMESTAMP"})
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default": "CURRENT_TIMESTAMP"})
     */
    protected $updatedAt;
}
