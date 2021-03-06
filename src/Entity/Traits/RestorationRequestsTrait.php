<?php
declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait RestorationRequestsTrait
 * @package App\Entity\Traits
 */
trait RestorationRequestsTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     * @Serializer\Type("boolean")
     */
    protected $requestMark;

    /**
     * @ORM\Column(type = "json_array", nullable = true)
     * @Gedmo\Versioned()
     * @Serializer\Type("array")
     */
/* Serializer\Type("ArrayCollection<App\Entity\User>") */
    protected $requestedByUsers;

    /**
     * @var $requestsCount
     * @Serializer\Type("integer")
     */
    protected $requestsCount;

    /**
     * Set requestMark
     * @param bool $requestMark
     * @return $this
     */
    public function setRequestMark(bool $requestMark): self
    {
        $this->requestMark = $requestMark;

        return $this;
    }

    /**
     * Get requestMark
     * @return bool
     */
    public function getRequestMark(): ?bool
    {
        return $this->requestMark;
    }

    /**
     * Set requestedByUsers
     * @param array $users
     * @return $this
     */
    public function setRequestedByUsers(array $users = null): self
    {
        $this->requestedByUsers = $users;

        return $this;
    }

    /**
     * Get requestedByUsers
     * @return array
     */
    public function getRequestedByUsers(): ?array
    {
        return $this->requestedByUsers;
    }

    /**
     * Set requestsCount
     * @param int $count
     * @return $this
     */
    public function setRequestsCount(int $count): self
    {
        $this->requestsCount = $count;

        return $this;
    }

    /**
     * Get requestsCount
     * @return int
     */
    public function getRequestsCount(): ?int
    {
        return count($this->getRequestedByUsers());
        //$arr = $this->getRequestedByUsers();
        //return count($arr);
    }

}
