<?php

namespace App\Entity;

use App\Entity\Traits\CommonTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FactoryEntity
 * @package App\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields = {"factoryName"},
 *     groups = {"factory_addition"})
 * @ORM\Table(name = "archive_factories")
 * @Gedmo\Loggable(logEntryClass = "App\Entity\LogEntity\FactoryLog")
 */

class FactoryEntity
{
    use CommonTrait;

    /**
     * @ORM\Column(type = "string")
     * @Assert\NotBlank(groups = {"factory_addition"})
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $factoryName;

    /**
     * Set factoryName
     * @param string $factoryName
     * @return FactoryEntity
     */
    public function setFactoryName($factoryName)
    {
        $this->factoryName = $factoryName;

        return $this;
    }

    /**
     * Get factoryName
     * @return string
     */
    public function getFactoryName()
    {
        return $this->factoryName;
    }

    public function __toString()
    {
        return $this->factoryName;
    }
}
