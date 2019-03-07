<?php
declare(strict_types=1);

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
     * Get id
     * @return int
     */
    public function getId()
    {
        return $this->user->getId();
    }

    /**
     * Get username
     * @return string
     */
    public function getUsername()
    {
        return $this->user->getUsernameCanonical();
    }

    /**
     * Get email
     * @return string
     */
    public function getEmail()
    {
        return $this->user->getEmailCanonical();
    }

    /**
     * Get salt
     * @return string
     */
    public function getSalt()
    {
        return $this->user->getSalt();
    }

    /**
     * Get lastLogin
     * @return \DateTime|null
     */
    public function getLastLogin()
    {
        return $this->user->getLastLogin();
    }

    /**
     * Get roles
     * @return array
     */
    public function getRoles()
    {
        return $this->user->getRoles();
    }

    /**
     * Get password (null)
     * @return string|void
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Emtpy password in User object
     * @return $this
     */
    public function eraseCredentials()
    {
        $this->user->setPassword(null);

        return $this;
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof KerberosUser) {
            return false;
        }
        if ($this->user->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
