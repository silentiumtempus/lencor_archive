<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\Ldap\Entry;

/**
 * Class UserFactory
 * @package App\Factory
 */
class UserFactory
{
    /**
     * @param Entry $kerberosUser
     * @param string $userIdAttribute
     * @return User
     * @throws \Exception
     */
    public function prepareKerberosUser(Entry $kerberosUser, string $userIdAttribute)
    {
        $user = new User();
        $email = ($kerberosUser->getAttribute('mail')[0]) ?? null;
        $user
            ->setUsername($kerberosUser->getAttribute('uid')[0])
            ->setUsernameCanonical(($kerberosUser->getAttribute('uid')[0]))
            ->setEmail($email)
            ->setEmailCanonical($email)
            ->setEnabled(true)
            ->setLastLogin(new \DateTime())
            ->setIsADUser(true)
            ->setADUserId($kerberosUser->getAttribute($userIdAttribute)[0]);

        return $user;
    }
}
