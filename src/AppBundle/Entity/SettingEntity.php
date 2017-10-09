<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SettingEntity
 * @package AppBundle\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields={"factory", "settingName"},
 *     groups={"setting_addition"}
 * )
 * @ORM\Table(name="archive_settings")
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\LogEntity\SettingLog")
 */
class SettingEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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
     * @return SettingEntity
     * @internal param FactoryEntity $factory
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

    public function __toString()
    {
        return $this->settingName;
    }
}
