<?php

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeletedChildrenTrait;
use App\Entity\Traits\DeletedStateTrait;
use App\Entity\Traits\RemovalMarkTrait;
use App\Entity\Traits\ModificationStampTrait;
use App\Entity\Traits\RestorationRequestsTrait;
use App\Entity\Traits\SlugTrait;
use App\Entity\Traits\SumErrorsTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ArchiveEntry
 * @package App\Entity
 * @ORM\Entity(repositoryClass = "App\Repository\ArchiveEntryRepository");
 * @UniqueEntity(
 *     fields = {"archiveNumber"},
 *     groups = {"entry_addition"}
 * )
 * @UniqueEntity(
 *     fields = {"registerNumber"},
 *     groups = {"entry_addition"},
 *     ignoreNull = true
 * )
 * @ORM\Table(name = "archive_entries")
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntities\ArchiveEntryLog")
 */
class ArchiveEntryEntity implements \JsonSerializable
{
    use CommonTrait;
    use RemovalMarkTrait;
    use SlugTrait;
    use SumErrorsTrait;
    use ModificationStampTrait;
    use RestorationRequestsTrait;
    use DeletedStateTrait;
    use DeletedChildrenTrait;

    /**
     * @ORM\Column(type = "integer", length = 4)
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Assert\Type("integer")
     * @Assert\Range(
     *     min = 1990,
     *     max = 2100,
     *     minMessage="Невозможно выбрать год ранее 1990",
     *     maxMessage="Невозможно выбрать год позднее 2100"
     * )
     * @Gedmo\Versioned()
     * @Serializer\Type("integer")
     */

    protected $year;

    /**
     * @ORM\ManyToOne(targetEntity = "FactoryEntity", cascade = {"persist"})
     * @ORM\JoinColumn(name = "factory_id", referencedColumnName="id")
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\FactoryEntity")
     */

    protected $factory;

    /**

     * @ORM\ManyToOne(targetEntity = "SettingEntity", cascade = {"persist"})
     * @ORM\JoinColumn(name = "setting_id", referencedColumnName = "id")
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\SettingEntity")
     */

    protected $setting;

    /**
     * @ORM\Column(type = "string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */

    protected $archiveNumber;

    /**
     * @ORM\Column(type = "string", nullable=true)
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */

    protected $registerNumber;

    /**
     * @ORM\Column(type = "string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */

    protected $contractNumber;

    /**
     * @ORM\Column(type = "string")
     * @Assert\Type("string")
     * @Assert\NotBlank(groups = {"entry_addition"})
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */

    protected $fullConclusionName;

    /**
     * @ORM\OneToOne(targetEntity = "FolderEntity", mappedBy = "archiveEntry", cascade = {"persist"} )
     * @Serializer\Type("App\Entity\FolderEntity")
     */

    protected $cataloguePath;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields = {"year", "archiveNumber"})
     * @ORM\Column(name = "slug", type = "string", length = 128)
     * @Serializer\Type("string")
     */

    private $slug;

    /*
   protected $fileName;
   protected $logFileName; */

    /**
     * Constructor
    */

    public function __construct()
    {
        $this->requestedByUsers = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->year . '/' . $this->factory->getFactoryName() . '/' . $this->archiveNumber;
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
     * @param FactoryEntity $factory
     * @return ArchiveEntryEntity
     */

    public function setFactory(FactoryEntity $factory = null)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Get factory
     * @return FactoryEntity
     */

    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set setting
     * @param SettingEntity $setting
     * @return ArchiveEntryEntity
     */

    public function setSetting(SettingEntity $setting = null)
    {
        $this->setting = $setting;

        return $this;
    }

    /**
     * Get setting
     * @return SettingEntity
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
     * @param FolderEntity $cataloguePath
     * @return ArchiveEntryEntity
     */

    public function setCataloguePath($cataloguePath)
    {
        $this->cataloguePath = $cataloguePath;

        return $this;
    }

    /**
     * Get cataloguePath
     * @return FolderEntity
     */

    public function getCataloguePath()
    {
        return $this->cataloguePath;
    }

    /**
     * Add cataloguePath
     * @param FolderEntity $cataloguePath
     * @return ArchiveEntryEntity
     */
    public function addCataloguePath(FolderEntity $cataloguePath)
    {
        $this->cataloguePath[] = $cataloguePath;

        return $this;
    }

    /**
     * Remove cataloguePath
     * @param FolderEntity $cataloguePath
     */

    public function removeCataloguePath(FolderEntity $cataloguePath)
    {
        $this->cataloguePath->removeElement($cataloguePath);
    }

    /**
     * Serializing object for update comparison
     * @return array|mixed
     */

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'year' => $this->getYear(),
            'factory' => $this->getFactory()->getId(),
            'setting' => $this->getSetting()->getId(),
            'archiveNumber' => $this->getArchiveNumber(),
            'registerNumber' => $this->getRegisterNumber(),
            'contractNumber' => $this->getContractNumber(),
            'fullConclusionName' => $this->getFullConclusionName(),
        ];
    }
}
