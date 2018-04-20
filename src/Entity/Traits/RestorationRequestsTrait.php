<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

trait RestorationRequestsTrait
{
    /**
     * @ORM\Column(type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Gedmo\Versioned()
     */

    protected $requestMark;

    /**
     * @ORM\Column(type = "json", nullable = true)
     * @Gedmo\Versioned()
     */

    protected $requestedByUsers;

    protected $requestsCount;

    /**
     * Set requestMark
     * @param bool $requestMark
     * @return $this
     */
    public function setRequestMark(bool $requestMark)
    {
        $this->requestMark = $requestMark;

        return $this;
    }

    /**
     * Get requestMark
     * @return bool
     */
    public function getRequestMark()
    {
        return $this->requestMark;
    }

    /**
     * Set requestedByUsers
     * @param array $users
     * @return $this
     */
    public function setRequestedByUsers(array $users = null)
    {
        $this->requestedByUsers = $users;

        return $this;
    }

    /**
     * Get requestedByUsers
     * @return array
     */
    public function getRequestedByUsers()
    {
        return $this->requestedByUsers;
    }

    /**
     * Set requestsCount
     * @param int $count
     * @return $this
     */
    public function setRequestsCount(int $count)
    {
        $this->requestsCount = $count;

        return $this;
    }

    /**
     * Get requestsCount
     * @return int
     */
    public function getRequestsCount()
    {
        return count($this->getRequestedByUsers());
    }

}