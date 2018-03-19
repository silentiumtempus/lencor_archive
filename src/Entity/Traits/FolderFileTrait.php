<?php

namespace App\Entity\Traits;

use App\Entity\FolderEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

trait FolderFileTrait
{

    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     */

    protected $requestMark;

    /**
     * @ORM\Column(type = "json", nullable = true)
     */

    protected $requestedByUsers;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable(on="create")
     * @Gedmo\Versioned()
     */

    protected $addTimestamp;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $addedByUserId;

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
     * Set addedByUserId
     * @param string $addedByUserId
     * @return $this
     */
    public function setAddedByUserId($addedByUserId)
    {
        $this->addedByUserId = $addedByUserId;

        return $this;
    }

    /**
     * Get addedByUserId
     * @return string
     */
    public function getAddedByUserId()
    {
        return $this->addedByUserId;
    }
}

