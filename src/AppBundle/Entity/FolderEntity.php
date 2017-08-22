<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 023 23.05.17
 * Time: 7:41
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Class FolderEntity
 * @package AppBundle\Entity
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
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\LogEntity\FolderLog")
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
     * @Gedmo\Slug(fields={"folderName"})
     * @ORM\Column(name="slug", type="string", length=128)
     */

    private $slug;

    public function __toString()
    {
        return $this->folderName;
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
     * Set folderName
     *
     * @param string $folderName
     *
     * @return FolderEntity
     */
    public function setFolderName($folderName)
    {
        $this->folderName = $folderName;

        return $this;
    }

    /**
     * Get folderName
     *
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }

    /**
     * Set addTimestamp
     *
     * @param \DateTime $addTimestamp
     *
     * @return FolderEntity
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
     * @return FolderEntity
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

    /**
     * Set archiveEntry
     *
     * @param \AppBundle\Entity\ArchiveEntryEntity $archiveEntry
     *
     * @return FolderEntity
     */
    public function setArchiveEntry(\AppBundle\Entity\ArchiveEntryEntity $archiveEntry = null)
    {
        $this->archiveEntry = $archiveEntry;

        return $this;
    }

    /**
     * Get archiveEntry
     *
     * @return \AppBundle\Entity\ArchiveEntryEntity
     */
    public function getArchiveEntry()
    {
        return $this->archiveEntry;
    }

    /**
     * Add childFolder
     *
     * @param \AppBundle\Entity\FolderEntity $childFolder
     *
     * @return FolderEntity
     */
    public function addChildFolder(\AppBundle\Entity\FolderEntity $childFolder)
    {
        $this->childFolders[] = $childFolder;

        return $this;
    }

    /**
     * Remove childFolder
     *
     * @param \AppBundle\Entity\FolderEntity $childFolder
     */
    public function removeChildFolder(\AppBundle\Entity\FolderEntity $childFolder)
    {
        $this->childFolders->removeElement($childFolder);
    }

    /**
     * Get childFolders
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildFolders()
    {
        return $this->childFolders;
    }

    /**
     * Set parentFolder
     *
     * @param \AppBundle\Entity\FolderEntity $parentFolder
     *
     * @return FolderEntity
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
     * Constructor
     */
    public function __construct()
    {
        $this->childFolders = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set lft
     *
     * @param integer $lft
     *
     * @return FolderEntity
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     *
     * @return FolderEntity
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     *
     * @return FolderEntity
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     *
     * @return FolderEntity
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return FolderEntity
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
     * Set deleteMark
     *
     * @param boolean $deleteMark
     *
     * @return FolderEntity
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
     * @return FolderEntity
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
}
