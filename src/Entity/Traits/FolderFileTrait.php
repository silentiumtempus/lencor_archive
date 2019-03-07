<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait FolderFileTrait
 * @package App\Entity\Traits
 */
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
    public function setParentFolder(FolderEntity $parentFolder = null): self
    {
        $this->parentFolder = $parentFolder;

        return $this;
    }

    /**
     * Get parentFolder
     * @return FolderEntity|null
     */
    public function getParentFolder(): ?FolderEntity
    {
        return $this->parentFolder;
    }

    /**
     * Set addTimestamp
     * @param \DateTime $addTimestamp
     * @return $this
     */
    public function setAddTimestamp($addTimestamp): self
    {
        $this->addTimestamp = $addTimestamp;

        return $this;
    }

    /**
     * Get addTimestamp
     * @return \DateTime|null
     */
    public function getAddTimestamp(): ?\DateTime
    {
        return $this->addTimestamp;
    }

    /**
     * Set addedByUser
     * @param User $addedByUser
     * @return $this
     */
    public function setAddedByUser(User $addedByUser): self
    {
        $this->addedByUser = $addedByUser;

        return $this;
    }

    /**
     * Get addedByUser
     * @return User|null
     */
    public function getAddedByUser(): ?User
    {
        return $this->addedByUser;
    }
}
