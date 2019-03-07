<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use JMS\Serializer\Annotation as Serializer;

/**
 * Trait CommonTrait
 * @package App\Entity\Traits
 */
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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
