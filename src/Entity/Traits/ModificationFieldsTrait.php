<?php

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

trait ModificationFieldsTrait
{
    /**
     * @var \DateTime $lastModified
     * @ORM\Column(type = "datetime")
     * @Gedmo\Timestampable(on = "update")
     * @Assert\DateTime()
     * @Gedmo\Versioned()
     * @Serializer\Type("DateTime")
     */

    protected $lastModified;

    /**
     * @ORM\ManyToOne(targetEntity = "User", cascade = {"persist"})
     * @ORM\JoinColumn(name = "modified_by_user", referencedColumnName = "id")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\User")
     */

    protected $modifiedByUser;

    /**
     * Set lastModified
     * @param \DateTime $lastModified
     * @return $this
     */

    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     * @return \DateTime
     */

    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Set modifiedByUser
     * @param User $modifiedByUser
     * @return $this
     */

    public function setModifiedByUserId(User $modifiedByUser)
    {
        $this->modifiedByUser = $modifiedByUser;

        return $this;
    }

    /**
     * Get modifiedByUser
     * @return User
     */

    public function getModifiedByUserId()
    {
        return $this->modifiedByUser;
    }
}