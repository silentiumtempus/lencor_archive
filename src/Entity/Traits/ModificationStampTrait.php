<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * Trait ModificationStampTrait
 * @package App\Entity\Traits
 */
trait ModificationStampTrait
{
    /**
     * @var \DateTime $lastModified
     * @ORM\Column(type = "datetime")
     * @Gedmo\Timestampable(on = "update")
     * @Assert\DateTime()
     * @Gedmo\Versioned()
     * @Serializer\Type("DateTime")
     */
    protected $lastModified;

    /**
     * @ORM\ManyToOne(targetEntity = "User", cascade = {"persist"})
     * @ORM\JoinColumn(name = "modified_by_user", referencedColumnName = "id", nullable = true)
     * @Gedmo\Versioned()
     * @Serializer\Type("App\Entity\User")
     */
    protected $modifiedByUser;

    /**
     * Set lastModified
     * @param \DateTime $lastModified
     * @return $this
     */
    public function setLastModified(\DateTime $lastModified): self
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     * @return \DateTime|null
     */
    public function getLastModified(): ?\DateTime
    {
        return $this->lastModified;
    }

    /**
     * Set modifiedByUser
     * @param User $modifiedByUser
     * @return $this
     */
    public function setModifiedByUser(User $modifiedByUser = null): self
    {
        $this->modifiedByUser = $modifiedByUser;

        return $this;
    }

    /**
     * Get modifiedByUser
     * @return User|null
     */
    public function getModifiedByUser(): ?User
    {
        return $this->modifiedByUser;
    }
}
