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
     * @param int $addedByUserId
     * @return $this
     */
    public function setAddedByUserId(int $addedByUserId)
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

    /**
     * Set requestMark
     * @param bool $requestMark
     * @return $this
     */
    public function setRequestMark(bool $requestMark)
    {
        $this->requestMark = $requestMark;

        return $this;
    }

    /**
     * Get requestMark
     * @return bool
     */
    public function getRequestMark()
    {

        return $this->requestMark;
    }

    /**
     * Set requestedByUsers
     * @param array $users
     * @return $this
     */
    public function setRequestedByUsers(array $users)
    {
        $this->requestedByUsers = $users;

        return $this;
    }

    /**
     * Get requestedByUsers
     * @return array
     */
    public function getRequestedByUsers()
    {

        return $this->requestedByUsers;
    }
}

