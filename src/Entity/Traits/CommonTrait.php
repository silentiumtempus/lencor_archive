<?php

namespace App\Entity\Traits;

trait CommonTrait
{
    /**
     * @ORM\Column(type = "integer", nullable = false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy = "AUTO")
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
