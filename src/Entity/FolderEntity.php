<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeletedChildrenTrait;
use App\Entity\Traits\DeletedStateTrait;
use App\Entity\Traits\RemovalMarkTrait;
use App\Entity\Traits\FolderFileTrait;
use App\Entity\Traits\RestorationRequestsTrait;
use App\Entity\Traits\SlugTrait;
use App\Entity\Traits\SumErrorsTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class FolderEntity
 * @package App\Entity
 * @Gedmo\Tree(type="nested")
 * @UniqueEntity(
 *     fields = {"parentFolder", "folderName"},
 *     groups = {"folder_common"}),
 * @ORM\Table(name = "archive_folders", uniqueConstraints = {
 *     @ORM\UniqueConstraint(
 *      name = "unique_folder",
 *      columns = {"folder_name", "parent_folder_id"}
 *     )
 * }),
 * @ORM\Entity(repositoryClass = "App\Repository\FolderRepository")
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntities\FolderLog")
 */
class FolderEntity
{
    use CommonTrait;
    use FolderFileTrait;
    use SlugTrait;
    use RemovalMarkTrait;
    use SumErrorsTrait;
    use RestorationRequestsTrait;
    use DeletedStateTrait;
    use DeletedChildrenTrait;

    /**
     * @ORM\OneToOne(targetEntity = "ArchiveEntryEntity", inversedBy = "cataloguePath")
     * @ORM\JoinColumn(name = "archive_entry_id", referencedColumnName = "id")
     * @Serializer\Type("App\Entity\ArchiveEntryEntity")
     */
    protected $archiveEntry;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */
    protected $folderName;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name = "lft", type = "integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name = "lvl", type = "integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name = "rgt", type = "integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity = "FolderEntity")
     * @ORM\JoinColumn(referencedColumnName = "id")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\FolderEntity")
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity = "FolderEntity", inversedBy = "childFolders")
     * @ORM\JoinColumn(referencedColumnName = "id")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\FolderEntity")
     */
    private $parentFolder;

    /**
     * @ORM\OneToMany(targetEntity = "FolderEntity", mappedBy = "parentFolder", cascade = {"persist"})
     * @ORM\OrderBy({"lft" = "ASC"})
     * @Serializer\Type("ArrayCollection<App\Entity\FolderEntity>")
     */
    private $childFolders;

    /**
     * @ORM\OneToMany(targetEntity="FileEntity", mappedBy="parentFolder", cascade = {"persist"})
     * @Serializer\Type("ArrayCollection<App\Entity\FileEntity>")
     */
    protected $files;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields = {"folderName"})
     * @ORM\Column(name = "slug", type = "string", length = 128)
     * @Serializer\Type("string")
     */
    private $slug;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->childFolders = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->requestedByUsers = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function __toString(): ?string
    {
        return $this->folderName;
    }

    /**
     * Set folderName
     * @param string $folderName
     * @return FolderEntity
     */
    public function setFolderName($folderName): self
    {
        $this->folderName = $folderName;

        return $this;
    }

    /**
     * Get folderName
     * @return string
     */
    public function getFolderName(): ?string
    {
        return $this->folderName;
    }

    /**
     * Set archiveEntry
     * @param ArchiveEntryEntity $archiveEntry
     * @return FolderEntity
     */
    public function setArchiveEntry(ArchiveEntryEntity $archiveEntry = null): self
    {
        $this->archiveEntry = $archiveEntry;

        return $this;
    }

    /**
     * Get archiveEntry
     * @return ArchiveEntryEntity
     */
    public function getArchiveEntry(): ?ArchiveEntryEntity
    {
        return $this->archiveEntry;
    }

    /**
     * Add childFolder
     * @param FolderEntity $childFolder
     * @return FolderEntity
     */
    public function addChildFolder(FolderEntity $childFolder): self
    {
        $this->childFolders[] = $childFolder;

        return $this;
    }

    /**
     * Remove childFolder
     * @param FolderEntity $childFolder
     */
    public function removeChildFolder(FolderEntity $childFolder): void
    {
        $this->childFolders->removeElement($childFolder);
    }

    /**
     * Get childFolders
     * @return ArrayCollection|null
     */
    public function getChildFolders(): ?ArrayCollection
    {
        return $this->childFolders;
    }

    /**
     * Add files
     * @param FileEntity $files
     * @return $this
     */
    public function addFiles(FileEntity $files): self
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Remove files
     * @param FileEntity $files
     */
    public function removeFiles(FileEntity $files): void
    {
        $this->files->removeElement($files);
    }

    /**
     * Get files
     * @return ArrayCollection|null
     */
    public function getFiles(): ?ArrayCollection
    {
        return $this->files;
    }

    /**
     * @param ArrayCollection|null $files
     * @return $this
     */

    public function setFiles(ArrayCollection $files = null): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Set lft
     * @param integer $lft
     * @return FolderEntity
     */
    public function setLft($lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     * @return integer
     */
    public function getLft(): int
    {
        return $this->lft;
    }

    /**
     * Set lvl
     * @param integer $lvl
     * @return FolderEntity
     */
    public function setLvl($lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     * @return integer
     */
    public function getLvl(): int
    {
        return $this->lvl;
    }

    /**
     * Set rgt
     * @param integer $rgt
     * @return FolderEntity
     */
    public function setRgt($rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     * @return integer
     */
    public function getRgt(): int
    {
        return $this->rgt;
    }

    /**
     * Set root
     * @param integer $root
     * @return FolderEntity
     */
    public function setRoot($root): self
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     * @return FolderEntity
     */
    public function getRoot(): self
    {
        return $this->root;
    }
}
