<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

trait DeleteStateTrait
{
    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     */

    protected $deleteMark;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     */

    protected $deletedByUserId;

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
     * Set deletedByUserId
     * @param string $deletedByUserId
     * @return $this
     */
    public function setDeletedByUserId($deletedByUserId)
    {
        $this->deletedByUserId = $deletedByUserId;

        return $this;
    }

    /**
     * Get deletedByUserId
     * @return string
     */
    public function getDeletedByUserId()
    {
        return $this->deletedByUserId;
    }
}