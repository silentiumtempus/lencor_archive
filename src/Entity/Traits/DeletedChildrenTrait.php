<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait DeletedChildrenTrait
 * @package App\Entity\Traits
 */
trait DeletedChildrenTrait
{

    /**
     * @ORM\Column(type = "integer", nullable = true)
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */

    protected $deletedChildren;

    /**
     * Set deleted
     * @param int $deletedChildren
     * @return $this
     */

    public function setDeletedChildren(int $deletedChildren)
    {
        $this->deletedChildren = $deletedChildren;

        return $this;
    }

    /**
     * Get requestMark
     * @return int
     */

    public function getDeletedChildren()
    {
        return $this->deletedChildren;
    }
}