<?php

namespace App\Entity\Traits;

use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait FolderFileTrait
{
    /**
     * @ORM\Column(type = "datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable(on = "create")
     * @Gedmo\Versioned()
     * @Serializer\Type("DateTime")
     */

    protected $addTimestamp;

    /**
     * @ORM\ManyToOne(targetEntity = "User", cascade = {"persist"})
     * @ORM\JoinColumn(name = "added_by_user", referencedColumnName = "id")
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\User")
     */

    protected $addedByUser;

    /**
     * Set parentFolder
     * @param FolderEntity $parentFolder
     * @return $this
     */

    public function setParentFolder(FolderEntity $parentFolder = null)
    {
        $this->parentFolder = $parentFolder;

        return $this;
    }

    /**
     * Get parentFolder
     * @return FolderEntity
     */

    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * Set addTimestamp
     * @param \DateTime $addTimestamp
     * @return $this
     */

    public function setAddTimestamp($addTimestamp)
    {
        $this->addTimestamp = $addTimestamp;

        return $this;
    }

    /**
     * Get addTimestamp
     * @return \DateTime
     */

    public function getAddTimestamp()
    {
        return $this->addTimestamp;
    }

    /**
     * Set addedByUser
     * @param User $addedByUser
     * @return $this
     */

    public function setAddedByUser(User $addedByUser)
    {
        $this->addedByUser = $addedByUser;

        return $this;
    }

    /**
     * Get addedByUser
     * @return string
     */

    public function getAddedByUser()
    {
        return $this->addedByUser;
    }
}
