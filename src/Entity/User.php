<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as DefaultUSer;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
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
     * @Serializer\Type("integer")
     */

    protected $id;

    /**
     * @var $username
     * @Serializer\Type("string")
     */
    
    protected $username;

    /**
     * @var $usernameCanonical
     * @Serializer\Type("string")
     */
    
    protected $usernameCanonical;

    /**
     * @var $email
     * @Serializer\Type("string")
     */

    protected $email;

    /**
     * @var $emailCanonical
     * @Serializer\Type("string")
     */

    protected $emailCanonical;

    /**
     * @var $enabled
     * @Serializer\Type("boolean")
     */

    protected $enabled;

    /**
     * @var $salt
     * @Serializer\Type("string")
     */

    protected $salt;

    /**
     * @var $password
     * @Serializer\Type("string")
     */

    protected $password;

    /**
     * @var $plainPassword
     * @Serializer\Type("string")
     */

    protected $plainPassword;

    /**
     * @var $lastLogin
     * @Serializer\Type("DateTime")
     */

    protected $lastLogin;

    /**
     * @var $confirmationToken
     * @Serializer\Type("string")
     */

    protected $confirmationToken;

    /**
     * @var $passwordRequestedAt
     * @Serializer\Type("DateTime")
     */

    protected $passwordRequestedAt;

    /**
     * @var $roles
     *
     */

    protected $roles;

    /**
     * @var $groups
     * @Serializer\Type("GroupInterface")
     */

    protected $groups;

    /**
     * @ORM\Column(name = "is_ad_user", type = "boolean", nullable = true)
     * @Assert\Type("boolean")
     * @Serializer\Type("boolean")
     */

    protected $isADUser;

    /**
     * @ORM\Column(name = "ad_user_id", type = "integer", nullable = true)
     * @Assert\Type("integer")
     * @Serializer\Type("integer")
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
