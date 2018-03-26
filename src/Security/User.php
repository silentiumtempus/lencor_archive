<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 * @package App\Security
 */
class User implements UserInterface, EquatableInterface
{
    private $id;
    private $username;
    private $password;
    private $salt;
    private $roles;

    /**
     * User constructor.
     * @param int $id
     * @param string $username
     * @param string $password
     * @param string $salt
     * @param array $roles
     */
    public function __construct(int $id = null, string $username = null, string $password = null, string $salt = null, array $roles = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     *
     */
    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }
        if ($this->id !== $user->getId()) {
            return false;
        }
        if ($this->username !== $user->getUsername()) {
            return false;
        }
        /*if ($this->password !== $user->getPassword()) {
            return false;
        } */

        return true;
    }
}
