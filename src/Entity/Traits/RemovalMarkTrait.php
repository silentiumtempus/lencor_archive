<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait RemovalMarkTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     * @Serializer\Type("boolean")
     */

    protected $removalMark;

    /**
     * @ORM\ManyToOne(targetEntity = "User", cascade = {"persist"})
     * @ORM\JoinColumn(name = "marked_by_user", referencedColumnName = "id")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\User")
     */

    protected $markedByUser;

    /**
     * Set removalMark
     * @param boolean $removalMark
     * @return $this
     */

    public function setRemovalMark($removalMark)
    {
        $this->removalMark = $removalMark;

        return $this;
    }

    /**
     * Get removalMark
     * @return boolean
     */

    public function getRemovalMark()
    {
        return $this->removalMark;
    }

    /**
     * Set markedByUser
     * @param string $markedByUser
     * @return $this
     */

    public function setMarkedByUser($markedByUser)
    {
        $this->markedByUser = $markedByUser;

        return $this;
    }

    /**
     * Get markedByUser
     * @return string
     */

    public function getMarkedByUser()
    {
        return $this->markedByUser;
    }
}
