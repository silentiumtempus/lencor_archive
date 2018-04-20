<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

trait DeleteStateTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     */

    protected $deleteMark;

    /**
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "deleted_by_user", referencedColumnName = "id")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     */

    protected $deletedByUser;

    /**
     * Set deleteMark
     * @param boolean $deleteMark
     * @return $this
     */
    public function setDeleteMark($deleteMark)
    {
        $this->deleteMark = $deleteMark;

        return $this;
    }

    /**
     * Get deleteMark
     * @return boolean
     */
    public function getDeleteMark()
    {
        return $this->deleteMark;
    }

    /**
     * Set deletedByUser
     * @param string $deletedByUser
     * @return $this
     */
    public function setDeletedByUser($deletedByUser)
    {
        $this->deletedByUser = $deletedByUser;

        return $this;
    }

    /**
     * Get deletedByUser
     * @return string
     */
    public function getDeletedByUser()
    {
        return $this->deletedByUser;
    }
}
