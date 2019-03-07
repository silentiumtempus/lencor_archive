<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeletedStateTrait;
use App\Entity\Traits\ModificationStampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FactoryEntity
 * @package App\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields = {"factoryName"},
 *     groups = {"factory_addition"}
 * )
 * @ORM\Table(name = "archive_factories")
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntities\FactoryLog")
 */
class FactoryEntity
{
    use CommonTrait;
    use DeletedStateTrait;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank(groups = {"factory_addition"})
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */
    protected $factoryName;

    /**
     * @ORM\OneToMany(targetEntity="SettingEntity", mappedBy = "factory", cascade = {"persist"})
     * @Serializer\Type("ArrayCollection<App\Entity\SettingEntity>")
     * @var SettingEntity[] | ArrayCollection
     */
    private $settings;

    /**
     * FactoryEntity constructor.
     */
    public function __construct()
    {
        $this->settings = new ArrayCollection();
    }

    /**
     * Set factoryName
     * @param string $factoryName
     * @return FactoryEntity
     */
    public function setFactoryName($factoryName): self
    {
        $this->factoryName = $factoryName;

        return $this;
    }

    /**
     * Get factoryName
     * @return string|null
     */
    public function getFactoryName(): ?string
    {
        return $this->factoryName;
    }

    /**
     * Add settings
     * @param SettingEntity $settings
     * @return $this
     */
    public function addSettings(SettingEntity $settings): self
    {
        $this->settings[] = $settings;

        return $this;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Remove settings
     * @param SettingEntity $settings
     */
    public function removeSettings(SettingEntity $settings): void
    {
        $this->settings->removeElement($settings);
    }

    /**
     * Get settings
     * @return ArrayCollection
     */
    public function getSettings(): ?ArrayCollection
    {
        return $this->settings;
    }

    /**
     * @return mixed
     */
    public function __toString(): ?string
    {
        return $this->factoryName;
    }
}
