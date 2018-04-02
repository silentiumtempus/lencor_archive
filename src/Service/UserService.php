<?php

namespace App\Service;

use App\Entity\User;
use App\Security\KerberosUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Class UserService
 * @package App\Service
 */
class UserService
{
    protected $em;
    protected $container;
    protected $encoderFactory;
    protected $defaultPassword;
    protected $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, EncoderFactoryInterface $encoderFactory)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->encoderFactory = $encoderFactory;
        $this->defaultPassword = $this->container->getParameter('default.password');
        $this->usersRepository = $this->em->getRepository('App:User');
    }

    /**
     * @param array $userIds
     * @return \App\Entity\User[]|array
     */
    public function getUsers(array $userIds)
    {
        return $this->usersRepository->findById($userIds);
    }

    /**
     * @param $username
     * @return \App\Entity\User[]|null
     */
    public function getUserByCanonicalName($username)
    {

        return $this->usersRepository->findOneByCanonicalName($username);
    }


    /**
     * @param array $remoteUser
     * @return User
     */
    public function createKerberosUser(array $remoteUser)
    {
        $user = $this->prepareKerberosUser($remoteUser);
        $hashedDefaultPassword = $this->setDefaultPassword($user);
        $user->setPassword($hashedDefaultPassword);
        $this->persistNewKerberosUser($user);

        return $user;
    }

    /**
     * @param User $user
     * @return string
     */
    private function setDefaultPassword(User $user)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($this->defaultPassword, null);
    }


    /**
     * @param array $kerberosUser
     * @return User
     */
    private function prepareKerberosUser(array $kerberosUser)
    {
        $user = new User();
        $user
            ->setUsername($kerberosUser->getAttribute('uid')[0])
            ->setUsernameCanonical(($kerberosUser->getAttribute('uid')[0]))
            ->setEmail($kerberosUser->getAttribute('mail'))
            ->setEmailCanonical($kerberosUser->getAttribute('mail'))
            ->setEnabled(true)
            ->setLastLogin(new \DateTime())
            ->setRoles(array('ROLE_USER'))
            ->setIsADUser(true)
            ->setADUserId($kerberosUser->getAttribute('employeeID'));

        return $user;
    }

    /**
     * @param User $user
     */
    private function persistNewKerberosUser(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
