<?php

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
 *     fields={"fileId", "parentFolderId"},
 *     groups={"file_checksum_error"}),
 * @ORM\Table(name="archive_checksum_errors", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *      name="unique_checksum_entry",
 *      columns={"file_id", "parent_folder_id"}
 *     )
 * }),
 * @Gedmo\Loggable(logEntryClass="App\Entity\Mappings\LogMappings\FileChecksumErrorLog")
 */
class FileChecksumError
{

    use CommonTrait;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FileEntity")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $fileId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FolderEntity")
     * @ORM\JoinColumn(name="parent_folder_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $parentFolderId;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable(on="create")
     * @Gedmo\Versioned()
     */

    protected $firstOccuredOn;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Gedmo\Timestampable()
     * @Gedmo\Versioned()
     */

    protected $lastCheckOn;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     */

    protected $lastCheckByUser;

    /**
    * @ORM\Column(name="status", type="integer")
    * @Gedmo\Versioned()
    */


    protected $status;

    /**
     * Set firstOccuredOn
     *
     * @param \DateTime $firstOccuredOn
     *
     * @return FileChecksumError
     */
    public function setFirstOccuredOn($firstOccuredOn)
    {
        $this->firstOccuredOn = $firstOccuredOn;

        return $this;
    }

    /**
     * Get firstOccuredOn
     *
     * @return \DateTime
     */
    public function getFirstOccuredOn()
    {
        return $this->firstOccuredOn;
    }

    /**
     * Set lastCheckOn
     *
     * @param \DateTime $lastCheckOn
     *
     * @return FileChecksumError
     */
    public function setLastCheckOn($lastCheckOn)
    {
        $this->lastCheckOn = $lastCheckOn;

        return $this;
    }

    /**
     * Get lastCheckOn
     *
     * @return \DateTime
     */
    public function getLastCheckOn()
    {
        return $this->lastCheckOn;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return FileChecksumError
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set fileId
     *
     * @param FileEntity $fileId
     *
     * @return FileChecksumError
     */
    public function setFileId(FileEntity $fileId = null)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return FileEntity
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set parentFolderId
     *
     * @param FolderEntity $parentFolderId
     *
     * @return FileChecksumError
     */
    public function setParentFolderId(FolderEntity $parentFolderId = null)
    {
        $this->parentFolderId = $parentFolderId;

        return $this;
    }

    /**
     * Get parentFolderId
     *
     * @return FolderEntity
     */
    public function getParentFolderId()
    {
        return $this->parentFolderId;
    }

    /**
     * Set lastCheckByUser
     *
     * @param string $lastCheckByUser
     *
     * @return FileChecksumError
     */
    public function setLastCheckByUser($lastCheckByUser)
    {
        $this->lastCheckByUser = $lastCheckByUser;

        return $this;
    }

    /**
     * Get lastCheckByUser
     *
     * @return string
     */
    public function getLastCheckByUser()
    {
        return $this->lastCheckByUser;
    }
}
