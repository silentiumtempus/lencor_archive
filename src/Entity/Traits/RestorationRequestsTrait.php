<?php

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\Column(type = "json", nullable = true)
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
     * @param ArrayCollection $users
     * @return $this
     */

    public function setRequestedByUsers(ArrayCollection $users = null)
    {
        $this->requestedByUsers = $users;

        return $this;
    }

    /**
     * Get requestedByUsers
     * @return ArrayCollection
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
        //$arr[] = $this->getRequestedByUsers();
        //return count($arr);
    }

}