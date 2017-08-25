<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 027 27.05.17
 * Time: 18:40
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class FileEntity
 * @package AppBundle\Entity
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
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\LogEntity\FileLog")
 */
class FileEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /*/**
     * @ORM\ManyToOne(targetEntity="ArchiveEntryEntity")
     * @ORM\JoinColumn(name="archive_entry_id", referencedColumnName="id")
     * @Gedmo\Versioned()
     */

    //protected $archiveEntry;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\File
     * @Gedmo\Versioned()
     */

    protected $fileName;

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
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
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
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return FileEntity
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set addTimestamp
     *
     * @param \DateTime $addTimestamp
     *
     * @return FileEntity
     */
    public function setAddTimestamp($addTimestamp)
    {
        $this->addTimestamp = $addTimestamp;

        return $this;
    }

    /**
     * Get addTimestamp
     *
     * @return \DateTime
     */
    public function getAddTimestamp()
    {
        return $this->addTimestamp;
    }

    /**
     * Set addedByUserId
     *
     * @param string $addedByUserId
     *
     * @return FileEntity
     */
    public function setAddedByUserId($addedByUserId)
    {
        $this->addedByUserId = $addedByUserId;

        return $this;
    }

    /**
     * Get addedByUserId
     *
     * @return string
     */
    public function getAddedByUserId()
    {
        return $this->addedByUserId;
    }

    /*
     * Set archiveEntry
     *
     * @param \AppBundle\Entity\ArchiveEntryEntity $archiveEntry
     *
     * @return FileEntity
     */
    /*public function setArchiveEntry(\AppBundle\Entity\ArchiveEntryEntity $archiveEntry = null)
    {
        $this->archiveEntry = $archiveEntry;

        return $this;
    } */

    /*
     * Get archiveEntry
     *
     * @return \AppBundle\Entity\ArchiveEntryEntity
     */
    /*public function getArchiveEntry()
    {
        return $this->archiveEntry;
    } */

    /**
     * Set parentFolder
     *
     * @param \AppBundle\Entity\FolderEntity $parentFolder
     *
     * @return FileEntity
     */
    public function setParentFolder(\AppBundle\Entity\FolderEntity $parentFolder = null)
    {
        $this->parentFolder = $parentFolder;

        return $this;
    }

    /**
     * Get parentFolder
     *
     * @return \AppBundle\Entity\FolderEntity
     */
    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return FileEntity
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set checksum
     *
     * @param string $checksum
     *
     * @return FileEntity
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum
     *
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Set deleteMark
     *
     * @param boolean $deleteMark
     *
     * @return FileEntity
     */
    public function setDeleteMark($deleteMark)
    {
        $this->deleteMark = $deleteMark;

        return $this;
    }

    /**
     * Get deleteMark
     *
     * @return boolean
     */
    public function getDeleteMark()
    {
        return $this->deleteMark;
    }

    /**
     * Set deletedByUserId
     *
     * @param string $deletedByUserId
     *
     * @return FileEntity
     */
    public function setDeletedByUserId($deletedByUserId)
    {
        $this->deletedByUserId = $deletedByUserId;

        return $this;
    }

    /**
     * Get deletedByUserId
     *
     * @return string
     */
    public function getDeletedByUserId()
    {
        return $this->deletedByUserId;
    }

    /**
     * Set sumError
     *
     * @param boolean $sumError
     *
     * @return FileEntity
     */
    public function setSumError($sumError)
    {
        $this->sumError = $sumError;

        return $this;
    }

    /**
     * Get sumError
     *
     * @return boolean
     */
    public function getSumError()
    {
        return $this->sumError;
    }
}
