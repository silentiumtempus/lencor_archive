<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package App\Security
 */
class UserProvider implements UserProviderInterface
{
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $username
     * @return User
     */
    public function loadUserByUsername($username)
    {
        $ldapConnection = new LDAPConnection($this->container);
        $ldap = Ldap::create('ext_ldap', array(
            'host' => $ldapConnection->getLDAPHost(),
            'port' => $ldapConnection->getLDAPPort(),
            'version' => $ldapConnection->getLDAPVersion(),
            'encryption' => $ldapConnection->getLDAPEncryption()
        ));

        $ldap->bind($ldapConnection->getLDAPUser(), $ldapConnection->getLDAPPassword());
        $query = $ldap->query($ldapConnection->getLDAPDC(), '(&(userPrincipalName='.$username.'))');
        $resultList = $query->execute();
        $user = $resultList[0];




        return new User(null, $user->getAttribute('userPrincipalName'), null, null, array('ROLE_ADMIN'));
    }

    /**
     * @param UserInterface $user
     * @return User
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Непредвиденная ошибка : Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
