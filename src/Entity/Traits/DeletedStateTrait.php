<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait DeletedStateTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     * @Serializer\Type("boolean")
     */

    protected $deleted = false;

    /**
     * Set deleted
     * @param bool $deleted
     * @return $this
     */

    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get requestMark
     * @return bool
     */

    public function getDeleted()
    {
        return $this->deleted;
    }
}