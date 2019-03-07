<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait RemovalMarkTrait
 * @package App\Entity\Traits
 */
trait RemovalMarkTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     * @Serializer\Type("boolean")
     */
    protected $removalMark;

    /**
     * @ORM\ManyToOne(targetEntity = "User", cascade = {"persist"})
     * @ORM\JoinColumn(name = "marked_by_user", referencedColumnName = "id")
     * @Assert\Type("integer")
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\User")
     */
    protected $markedByUser;

    /**
     * Set removalMark
     * @param boolean $removalMark
     * @return $this
     */
    public function setRemovalMark($removalMark): self
    {
        $this->removalMark = $removalMark;

        return $this;
    }

    /**
     * Get removalMark
     * @return boolean
     */
    public function getRemovalMark(): ?bool
    {
        return $this->removalMark;
    }

    /**
     * Set markedByUser
     * @param User|null $markedByUser
     * @return $this
     */
    public function setMarkedByUser(User $markedByUser = null): self
    {
        $this->markedByUser = $markedByUser;

        return $this;
    }

    /**
     * Get markedByUser
     * @return User|null
     */
    public function getMarkedByUser(): ?User
    {
        return $this->markedByUser;
    }
}
