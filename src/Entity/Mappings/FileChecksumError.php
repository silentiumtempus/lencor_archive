<?php
declare(strict_types=1);

namespace App\Entity\Mappings;

use App\Entity\Traits\CommonTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\FolderEntity;
use App\Entity\FileEntity;

/**
 * Class FileChecksumError
 * @package App\Entity\Mappings
 * @ORM\Entity()
 * @UniqueEntity(
 *     fields = {"fileId", "parentFolderId"},
 *     groups = {"file_checksum_error"}),
 * @ORM\Table(name = "archive_checksum_errors", uniqueConstraints = {
 *     @ORM\UniqueConstraint(
 *      name = "unique_checksum_entry",
 *      columns = {"file_id", "parent_folder_id"}
 *     )
 * }),
 * @Gedmo\Loggable(logEntryClass = "App\Entity\Mappings\LogMappings\FileChecksumErrorLog")
 */
class FileChecksumError
{
    use CommonTrait;

    /**
     * @ORM\OneToOne(targetEntity = "App\Entity\FileEntity")
     * @ORM\JoinColumn(name = "file_id", referencedColumnName = "id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */
    protected $fileId;

    /**
     * @ORM\ManyToOne(targetEntity = "App\Entity\FolderEntity")
     * @ORM\JoinColumn(name="parent_folder_id", referencedColumnName = "id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */
    protected $parentFolderId;

    /**
     * @ORM\Column(type = "datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable(on = "create")
     * @Gedmo\Versioned()
     */
    protected $firstOccuredOn;

    /**
     * @ORM\Column(type = "datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable()
     * @Gedmo\Versioned()
     */
    protected $lastCheckOn;

    /**
     * @ORM\Column(type = "integer")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     */
    protected $lastCheckByUser;

    /**
    * @ORM\Column(name = "status", type = "integer")
    * @Gedmo\Versioned()
    */
    protected $status;

    /**
     * Set firstOccuredOn
     * @param \DateTime $firstOccuredOn
     * @return FileChecksumError
     */
    public function setFirstOccuredOn($firstOccuredOn): self
    {
        $this->firstOccuredOn = $firstOccuredOn;

        return $this;
    }

    /**
     * Get firstOccuredOn
     * @return \DateTime|null
     */
    public function getFirstOccuredOn(): ?\DateTime
    {
        return $this->firstOccuredOn;
    }

    /**
     * Set lastCheckOn
     * @param \DateTime $lastCheckOn
     * @return FileChecksumError
     */
    public function setLastCheckOn($lastCheckOn): self
    {
        $this->lastCheckOn = $lastCheckOn;

        return $this;
    }

    /**
     * Get lastCheckOn
     * @return \DateTime|null
     */
    public function getLastCheckOn(): ?\DateTime
    {
        return $this->lastCheckOn;
    }

    /**
     * Set status
     * @param integer $status
     * @return FileChecksumError
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set fileId
     * @param FileEntity $fileId
     * @return FileChecksumError
     */
    public function setFileId(FileEntity $fileId = null): self
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     * @return FileEntity|null
     */
    public function getFileId(): ?FileEntity
    {
        return $this->fileId;
    }

    /**
     * Set parentFolderId
     * @param FolderEntity $parentFolderId
     * @return FileChecksumError
     */
    public function setParentFolderId(FolderEntity $parentFolderId = null): self
    {
        $this->parentFolderId = $parentFolderId;

        return $this;
    }

    /**
     * Get parentFolderId
     * @return FolderEntity|null
     */
    public function getParentFolderId(): ?FolderEntity
    {
        return $this->parentFolderId;
    }

    /**
     * Set lastCheckByUser
     * @param string $lastCheckByUser
     * @return FileChecksumError
     */
    public function setLastCheckByUser($lastCheckByUser): self
    {
        $this->lastCheckByUser = $lastCheckByUser;

        return $this;
    }

    /**
     * Get lastCheckByUser
     * @return string|null
     */
    public function getLastCheckByUser(): ?string
    {
        return $this->lastCheckByUser;
    }
}
