<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 023 23.02.17
 * Time: 15:57
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FactoryEntity
 * @package AppBundle\Entity
 * @ORM\Entity;
 * @UniqueEntity(
 *     fields={"factoryName"},
 *     groups={"factory_addition"})
 * @ORM\Table(name="archive_factories")
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\LogEntity\FactoryLog")
 */

class FactoryEntity
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank(groups={"factory_addition"})
     * @Assert\Type("string")
     * @Gedmo\Versioned()
     */

    protected $factoryName;

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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
