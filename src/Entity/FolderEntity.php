<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class FolderEntity
 * @package App\Entity
 * @Gedmo\Tree(type="nested")
 * @UniqueEntity(
 *     fields={"parentFolder", "folderName"},
 *     groups={"folder_creation"}),
 * @ORM\Table(name="archive_folders", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *      name="unique_folder",
 *      columns={"folder_name", "parent_folder_id"}
 *     )
 * }),
 * @ORM\Entity(repositoryClass="App\Repository\FolderRepository")
 * @Gedmo\Loggable(logEntryClass="App\Entity\LogEntity\FolderLog")
 */
class FolderEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="ArchiveEntryEntity", inversedBy="cataloguePath")
     * @ORM\JoinColumn(name="archive_entry_id", referencedColumnName="id")
     */

    protected $archiveEntry;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $folderName;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\Versioned()
     */

    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\Versioned()
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\Versioned()
     */

    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="FolderEntity")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Gedmo\Versioned()
     */

    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="FolderEntity", inversedBy="childFolders")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Gedmo\Versioned()
     */

    private $parentFolder;

    /**
     * @ORM\OneToMany(targetEntity="FolderEntity", mappedBy="parentFolder")
     * @ORM\OrderBy({"lft" = "ASC"})
     */

    private $childFolders;

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
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type("integer")
     */

    protected $sumErrors;

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
     * @Gedmo\Slug(fields={"folderName"})
     * @ORM\Column(name="slug", type="string", length=128)
     */

    private $slug;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->childFolders = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->folderName;
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
     * Set folderName
     * @param string $folderName
     * @return FolderEntity
     */
    public function setFolderName($folderName)
    {
        $this->folderName = $folderName;

        return $this;
    }

    /**
     * Get folderName
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }

    /**
     * Set addTimestamp
     * @param \DateTime $addTimestamp
     * @return FolderEntity
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
     * @return FolderEntity
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
     * Set archiveEntry
     * @param ArchiveEntryEntity $archiveEntry
     * @return FolderEntity
     */
    public function setArchiveEntry(ArchiveEntryEntity $archiveEntry = null)
    {
        $this->archiveEntry = $archiveEntry;

        return $this;
    }

    /**
     * Get archiveEntry
     * @return ArchiveEntryEntity
     */
    public function getArchiveEntry()
    {
        return $this->archiveEntry;
    }

    /**
     * Add childFolder
     * @param FolderEntity $childFolder
     * @return FolderEntity
     */
    public function addChildFolder(FolderEntity $childFolder)
    {
        $this->childFolders[] = $childFolder;

        return $this;
    }

    /**
     * Remove childFolder
     * @param FolderEntity $childFolder
     */
    public function removeChildFolder(FolderEntity $childFolder)
    {
        $this->childFolders->removeElement($childFolder);
    }

    /**
     * Get childFolders
     * @return ArrayCollection
     */
    public function getChildFolders()
    {
        return $this->childFolders;
    }

    /**
     * Set parentFolder
     * @param FolderEntity $parentFolder
     * @return FolderEntity
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
     * Set lft
     * @param integer $lft
     * @return FolderEntity
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set lvl
     * @param integer $lvl
     * @return FolderEntity
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set rgt
     * @param integer $rgt
     * @return FolderEntity
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     * @param integer $root
     * @return FolderEntity
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set slug
     * @param string $slug
     * @return FolderEntity
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
     * Set deleteMark
     * @param boolean $deleteMark
     * @return FolderEntity
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
     * @return FolderEntity
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
     * Set sumErrors
     * @param integer $sumErrors
     * @return FolderEntity
     */
    public function setSumErrors($sumErrors)
    {
        $this->sumErrors = $sumErrors;

        return $this;
    }

    /**
     * Get sumErrors
     * @return integer
     */
    public function getSumErrors()
    {
        return $this->sumErrors;
    }
}
