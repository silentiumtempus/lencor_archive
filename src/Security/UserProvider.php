<?php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Service\LDAPService;
use App\Service\UserService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package App\Security
 */
class UserProvider implements UserProviderInterface
{
    protected $LDAPService;
    protected $userService;
    protected $encoderFactory;

    public function __construct(LDAPService $LDAPService, UserService $userService)
    {
        $this->LDAPService = $LDAPService;
        $this->userService = $userService;
    }

    /**
     * @param string $username
     * @return User|User[]|UserInterface|null
     * @throws \Exception
     */
    public function loadUserByUsername($username)
    {
        $remoteUser = $this->LDAPService->authorizeLDAPUserByUserName($username);
        $localUser = $this->userService->getUserByCanonicalName($username);
        if (!$localUser) {
            $localUser = $this->userService->createKerberosUser($remoteUser);
        } else {
            $this->userService->updateLastLogin($localUser);
        }

        return $localUser;
    }

    /**
     * @param UserInterface $user
     * @return User|User[]|UserInterface|null
     * @throws \Exception
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof KerberosUser) {
            throw new UnsupportedUserException(
                sprintf('Unexpected error: Instances of "%s" are not supported.', get_class($user))
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
        return KerberosUser::class === $class;
    }
}
