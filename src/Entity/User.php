<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as DefaultUSer;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class KerberosUser
 * @package App\Entity
 * @ORM\Entity
 * @ORM\Table(name = "main_users")
 */

class User extends DefaultUSer
{
    /**
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name = "is_ad_user", type = "boolean")
     * @Assert\Type("boolean")
     */
    protected $isADUser;

    /**
     * @ORM\Column(name = "ad_user_id", type = "integer", nullable = true)
     * @Assert\Type("integer")
     */
    protected $ADUserId;

    /**
     * Set isADUserr
     * @param boolean $isADUser
     * @return User
     */
    public function setIsADUser($isADUser)
    {
        $this->isADUser = $isADUser;

        return $this;
    }

    /**
     * Get isADUser
     * @return boolean
     */
    public function getIsADUser()
    {
        return $this->isADUser;
    }

    /**
     * Set ADUserId
     * @param $ADUserId
     * @return $this
     */
    public function setADUserId($ADUserId)
    {
        $this->ADUserId = $ADUserId;

        return $this;
    }

    /**
     * Get ADUserId
     * @return integer
     */
    public function getADUserId()
    {
        return $this->ADUserId;
    }

}
