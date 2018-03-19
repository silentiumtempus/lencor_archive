<?php

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SettingEntity
 * @package App\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields={"factory", "settingName"},
 *     groups={"setting_addition"}
 * )
 * @ORM\Table(name="archive_settings")
 * @Gedmo\Loggable(logEntryClass="App\Entity\LogEntity\SettingLog")
 */
class SettingEntity
{

    use CommonTrait;

    /**
     * @ORM\ManyToOne(targetEntity="FactoryEntity")
     * @ORM\JoinColumn(name="factory_id", referencedColumnName="id")
     * @Gedmo\Versioned()
     */

    protected $factory;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */
    protected $settingName;

    /**
     * Set name
     * @param string $settingName
     * @return SettingEntity
     */
    public function setName($settingName)
    {
        $this->settingName = $settingName;

        return $this;
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->settingName;
    }

    /**
     * Set factory
     * @param FactoryEntity $factory
     * @return SettingEntity
     * @internal param FactoryEntity $factory
     */
    public function setFactory(FactoryEntity $factory = null)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Get factory
     * @return \App\Entity\FactoryEntity
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set settingName
     * @param string $settingName
     * @return SettingEntity
     */
    public function setSettingName($settingName)
    {
        $this->settingName = $settingName;

        return $this;
    }

    /**
     * Get settingName
     * @return string
     */
    public function getSettingName()
    {
        return $this->settingName;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->settingName;
    }
}
