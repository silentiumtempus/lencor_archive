<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FileEntity
 * @package App\Entity
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"fileName", "parentFolder"},
 *     groups={"file_upload"}),
 * @ORM\Table(name="archive_files", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *      name="unique_file",
 *      columns={"file_name", "parent_folder_id"}
 *     )
 * }),
 * @Gedmo\Loggable(logEntryClass="App\Entity\LogEntity\FileLog")
 */
class FileEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $fileName;

    /**
     * @Assert\NotBlank
     * @Assert\File
     * @Gedmo\Versioned()
     */

    protected $files;

    /**
     * @ORM\ManyToOne(targetEntity="FolderEntity")
     * @ORM\JoinColumn(name="parent_folder_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Gedmo\Versioned()
     */

    protected $parentFolder;

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
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $checksum;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\Type("boolean")
     */

    protected $sumError;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     */

    protected $deleteMark;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\Type("smallint")
     * @Gedmo\Versioned()
     */

    protected $deletedByUserId;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"fileName"})
     * @ORM\Column(name="slug", type="string", length=128)
     */

    private $slug;

    public function __toString()
    {
        return $this->fileName;
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set files
     * @param array $files
     * @return FileEntity
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get files
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set fileName
     * @param string $fileName
     * @return FileEntity
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set addTimestamp
     * @param \DateTime $addTimestamp
     * @return FileEntity
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
     * @return FileEntity
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

    /**
     * Set parentFolder
     * @param FolderEntity $parentFolder
     * @return FileEntity
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
     * Set slug
     * @param string $slug
     * @return FileEntity
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set checksum
     * @param string $checksum
     * @return FileEntity
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Set deleteMark
     * @param boolean $deleteMark
     * @return FileEntity
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
     * @return FileEntity
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

    /**
     * Set sumError
     * @param boolean $sumError
     * @return FileEntity
     */
    public function setSumError($sumError)
    {
        $this->sumError = $sumError;

        return $this;
    }

    /**
     * Get sumError
     * @return boolean
     */
    public function getSumError()
    {
        return $this->sumError;
    }
}
