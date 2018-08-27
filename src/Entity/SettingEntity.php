<?php

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use App\Entity\Traits\DeletedStateTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SettingEntity
 * @package App\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields = {"factory", "settingName"},
 *     groups = {"setting_addition"}
 * )
 * @ORM\Table(name = "archive_settings")
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntities\SettingLog")
 */
class SettingEntity
{
    use CommonTrait;
    use DeletedStateTrait;

    /**
     * @ORM\ManyToOne(targetEntity = "FactoryEntity", inversedBy = "settings", cascade = {"persist"})
     * @ORM\JoinColumn(name = "factory_id", referencedColumnName = "id")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\FactoryEntity")
     */

    protected $factory;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     * @Serializer\Type("string")
     */

    protected $settingName;

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
     * @return FactoryEntity
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
