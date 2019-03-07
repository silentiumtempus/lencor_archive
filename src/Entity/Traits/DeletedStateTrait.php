<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait DeletedStateTrait
 * @package App\Entity\Traits
 */
trait DeletedStateTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     * @Serializer\Type("boolean")
     */
    protected $deleted = false;

    /**
     * Set deleted
     * @param bool $deleted
     * @return $this
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get requestMark
     * @return bool|null
     */
    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }
}
