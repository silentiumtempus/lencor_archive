<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class KerberosUser
 * @package App\Security
 */
class KerberosUser implements UserInterface, EquatableInterface
{
    protected $user;

    /**
     * KerberosUser constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->user->getUsernameCanonical();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->user->getId();
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->user->getUsernameCanonical()
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->user->getSalt();
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->user->getRoles();
    }

    /**
     * @return string|void
     */
    public function getPassword()
    {
        return null;
    }

    /**
     *
     */
    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof KerberosUser) {
            return false;
        }
        if ($this->user->getId() !== $user->getId()) {
            return false;
        }
        if ($this->user->getUsername() !== $user->getUsername()) {
            return false;
        }
        /*if ($this->password !== $user->getPassword()) {
            return false;
        } */

        return true;
    }
}
