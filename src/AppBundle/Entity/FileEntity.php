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
     * @Gedmo\Timestampable(on="update")
     * @Gedmo\Versioned()
     */

    protected $lastModified;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $modifiedByUserId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $checksum;

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
     * Set lastModified
     *
     * @param \DateTime $lastModified
     *
     * @return FileEntity
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Set modifiedByUserId
     *
     * @param string $modifiedByUserId
     *
     * @return FileEntity
     */
    public function setModifiedByUserId($modifiedByUserId)
    {
        $this->modifiedByUserId = $modifiedByUserId;

        return $this;
    }

    /**
     * Get modifiedByUserId
     *
     * @return string
     */
    public function getModifiedByUserId()
    {
        return $this->modifiedByUserId;
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
}
