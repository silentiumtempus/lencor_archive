<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 001 01.03.17
 * Time: 13:50
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class ArchiveEntry
 * @package AppBundle\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields={"archiveNumber"},
 *     groups={"entry_addition"}
 * )
 * @UniqueEntity(
 *     fields={"registerNumber"},
 *     groups={"entry_addition"},
 *     ignoreNull=true
 * )
 * @ORM\Table(name="archive_entries")
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\LogEntity\ArchiveEntryLog")
 */
class ArchiveEntryEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    protected $id;

    /**
     * @ORM\Column(type="integer", length=4)
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Assert\Type("integer")
     * @Assert\Range(
     *     min = 1990,
     *     max = 2100,
     *     minMessage="Невозможно выбрать год ранее 1990",
     *     maxMessage="Невозможно выбрать год позднее 2100"
     * )
     * @Gedmo\Versioned()
     */

    protected $year;

    /**
     * @ORM\ManyToOne(targetEntity="FactoryEntity")
     * @ORM\JoinColumn(name="factory_id", referencedColumnName="id")
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Gedmo\Versioned()
     */

    protected $factory;

    /**

     * @ORM\ManyToOne(targetEntity="SettingEntity")
     * @ORM\JoinColumn(name="setting_id", referencedColumnName="id")
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Gedmo\Versioned()
     */

    protected $setting;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Gedmo\Versioned()
     */

    protected $archiveNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $registerNumber;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Gedmo\Versioned()
     */

    protected $contractNumber;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"entry_addition"})
     * @Gedmo\Versioned()
     */

    protected $fullConclusionName;

    /**
     * @ORM\OneToOne(targetEntity="FolderEntity", mappedBy="archiveEntry" )
     */

    protected $cataloguePath;

    /**
     * @var \DateTime $lastModified
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime()
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
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\Type("smallint")
     */

    protected $sumErrors;

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
     * @Gedmo\Slug(fields={"year", "archiveNumber"})
     * @ORM\Column(name="slug", type="string", length=128)
     */

    private $slug;

    /*
   protected $fileName;
   protected $logFileName; */


    /**
     * Get id
     * @return integer
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set year
     * @param integer $year
     * @return ArchiveEntryEntity
     */

    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set factory
     * @param \AppBundle\Entity\FactoryEntity $factory
     * @return ArchiveEntryEntity
     */

    public function setFactory(\AppBundle\Entity\FactoryEntity $factory = null)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Get factory
     * @return \AppBundle\Entity\FactoryEntity
     */

    public function getFactory()
    {

        return $this->factory;
    }

    /**
     * Set setting
     * @param \AppBundle\Entity\SettingEntity $setting
     * @return ArchiveEntryEntity
     */

    public function setSetting(\AppBundle\Entity\SettingEntity $setting = null)
    {
        $this->setting = $setting;

        return $this;
    }

    /**
     * Get setting
     * @return \AppBundle\Entity\SettingEntity
     */

    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Set archiveNumber
     * @param string $archiveNumber
     * @return ArchiveEntryEntity
     */

    public function setArchiveNumber($archiveNumber)
    {
        $this->archiveNumber = $archiveNumber;

        return $this;
    }

    /**
     * Get archiveNumber
     * @return string
     */

    public function getArchiveNumber()
    {
        return $this->archiveNumber;
    }

    /**
     * Set registerNumber
     * @param string $registerNumber
     * @return ArchiveEntryEntity
     */

    public function setRegisterNumber($registerNumber)
    {
        $this->registerNumber = $registerNumber;

        return $this;
    }

    /**
     * Get registerNumber
     * @return string
     */

    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * Set contractNumber
     * @param string $contractNumber
     * @return ArchiveEntryEntity
     */

    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    /**
     * Get contractNumber
     * @return string
     */

    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * Set fullConclusionName
     * @param string $fullConclusionName
     * @return ArchiveEntryEntity
     */

    public function setFullConclusionName($fullConclusionName)
    {
        $this->fullConclusionName = $fullConclusionName;

        return $this;
    }

    /**
     * Get fullConclusionName
     * @return string
     */

    public function getFullConclusionName()
    {
        return $this->fullConclusionName;
    }

    /**
     * Set cataloguePath
     * @param integer $cataloguePath
     * @return ArchiveEntryEntity
     */

    public function setCataloguePath($cataloguePath)
    {

        $this->cataloguePath = $cataloguePath;

        return $this;
    }

    /**
     * Get cataloguePath
     * @return string
     */

    public function getCataloguePath()
    {
        return $this->cataloguePath;
    }

    /**
     * Set lastModified
     * @param \DateTime $lastModified
     * @return ArchiveEntryEntity
     */

    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     * @return \DateTime
     */

    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Set modifiedByUserId
     * @param string $modifiedByUserId
     * @return ArchiveEntryEntity
     */

    public function setModifiedByUserId($modifiedByUserId)
    {
        $this->modifiedByUserId = $modifiedByUserId;

        return $this;
    }

    /**
     * Get modifiedByUserId
     * @return string
     */

    public function getModifiedByUserId()
    {
        return $this->modifiedByUserId;
    }

    /**
     * Set deleteMark
     * @param boolean $deleteMark
     * @return ArchiveEntryEntity
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
     * Constructor
     */
    public function __construct()
    {
        $this->cataloguePath = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add cataloguePath
     *
     * @param \AppBundle\Entity\FolderEntity $cataloguePath
     *
     * @return ArchiveEntryEntity
     */
    public function addCataloguePath(\AppBundle\Entity\FolderEntity $cataloguePath)
    {
        $this->cataloguePath[] = $cataloguePath;

        return $this;
    }

    /**
     * Remove cataloguePath
     *
     * @param \AppBundle\Entity\FolderEntity $cataloguePath
     */
    public function removeCataloguePath(\AppBundle\Entity\FolderEntity $cataloguePath)
    {
        $this->cataloguePath->removeElement($cataloguePath);
    }

    /**
     * Set deletedByUserId
     *
     * @param string $deletedByUserId
     *
     * @return ArchiveEntryEntity
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
     * Set slug
     *
     * @param string $slug
     *
     * @return ArchiveEntryEntity
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
     * Set sumErrors
     *
     * @param integer $sumErrors
     *
     * @return ArchiveEntryEntity
     */
    public function setSumErrors($sumErrors)
    {
        $this->sumErrors = $sumErrors;

        return $this;
    }

    /**
     * Get sumErrors
     *
     * @return integer
     */
    public function getSumErrors()
    {
        return $this->sumErrors;
    }
}
