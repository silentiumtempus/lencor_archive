<?php

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait ModificationFieldsTrait
{
    /**
     * @var \DateTime $lastModified
     * @ORM\Column(type = "datetime")
     * @Gedmo\Timestampable(on = "update")
     * @Assert\DateTime()
     * @Gedmo\Versioned()
     */

    protected $lastModified;

    /**
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "modified_by_user", referencedColumnName = "id")
     * @Gedmo\Versioned()
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