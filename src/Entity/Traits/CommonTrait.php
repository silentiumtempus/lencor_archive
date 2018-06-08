<?php

namespace App\Entity\Traits;

use JMS\Serializer\Annotation as Serializer;

trait CommonTrait
{
    /**
     * @ORM\Column(type = "integer", nullable = false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "AUTO")
     * @Serializer\Type("integer")
     */

    protected $id;

    /**
     * Get id
     * @return integer
     */

    public function getId()
    {
        return $this->id;
    }
}
