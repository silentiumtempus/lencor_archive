<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Class UserService
 * @package App\Service
 */
class UserService
{
    private $em;
    private $container;
    private $userFactory;
    private $encoderFactory;
    private $defaultPassword;
    private $userIDAttribute;
    private $LDAPAdminsGroup;
    private $usersRepository;
    private $serializerService;

    /**
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param EncoderFactoryInterface $encoderFactory
     * @param SerializerService $serializerService
     * @param UserFactory $userFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        EncoderFactoryInterface $encoderFactory,
        SerializerService $serializerService,
        UserFactory $userFactory
    )
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->userFactory = $userFactory;
        $this->encoderFactory = $encoderFactory;
        $this->serializerService = $serializerService;
        $this->defaultPassword = $this->container->getParameter('default.password');
        $this->userIDAttribute = $this->container->getParameter('ldap.userid.attribute');
        $this->LDAPAdminsGroup = $this->container->getParameter('ldap.admins.group');
        $this->usersRepository = $this->em->getRepository('App:User');
    }

    /**
     * @param array $userIds
     * @return \App\Entity\User[]|null
     */
    public function getUsers(array $userIds): ?User
    {
        return $this->usersRepository->findById($userIds);
    }

    /**
     * @param $username
     * @return \App\Entity\User[]|null
     */
    public function getUserByCanonicalName($username): ?User
    {
        return $this->usersRepository->findOneByUsernameCanonical($username);
    }

    /**
     * @param Entry $remoteUser
     * @return User
     * @throws \Exception
     */
    public function createKerberosUser(Entry $remoteUser): User
    {
        $user = $this->prepareKerberosUser($remoteUser);
        $user->setPassword($this->setDefaultPassword($user));
        $this->persistNewKerberosUser($user);

        return $user;
    }

    /**
     * @param User $user
     * @return string
     */
    private function setDefaultPassword(User $user): string
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($this->defaultPassword, null);
    }

    /**
     * @param Entry $kerberosUser
     * @return User
     * @throws \Exception
     */
    private function prepareKerberosUser(Entry $kerberosUser): User
    {
        $user = $this->userFactory->prepareKerberosUser($kerberosUser, $this->userIDAttribute);
        $groups = ($kerberosUser->getAttribute('memberOf')) ?? null;
        if ($groups) {
            $group_names = [];
            foreach ($groups as $group) {
                preg_match_all("/([^=]+)=([^,]+) /x", $group, $g);
                $group_names[] = array_combine($g[1], $g[2])['CN'];
            }
            if (in_array($this->LDAPAdminsGroup, $group_names)) {
                $user->addRole('ROLE_ADMIN');
            }
        }

        return $user;
    }

    /**
     * @param User $user
     */
    private function persistNewKerberosUser(User $user): void
    {
        $this->em->persist($user);
        $this->serializerService->serializeUsers();
        $this->em->flush();
    }

    /**
     * @param User $user
     * @throws \Exception
     */
    public function updateLastLogin(User $user): void
    {
        $user->setLastLogin(new \DateTime());
        $this->serializerService->serializeUsers();
        $this->em->flush();
    }
}
