<?php

namespace App\Service;

use App\Entity\User;
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
    protected $em;
    protected $container;
    protected $encoderFactory;
    protected $defaultPassword;
    protected $userIDAttribute;
    protected $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, EncoderFactoryInterface $encoderFactory)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->encoderFactory = $encoderFactory;
        $this->defaultPassword = $this->container->getParameter('default.password');
        $this->userIDAttribute = $this->container->getParameter('ldap.userid.attribute');
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

        return $this->usersRepository->findOneByUsernameCanonical($username);
    }

    /**
     * @param Entry $remoteUser
     * @return User
     */
    public function createKerberosUser(Entry $remoteUser)
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
     * @param Entry $kerberosUser
     * @return User
     */
    private function prepareKerberosUser(Entry $kerberosUser)
    {
        $user = new User();
        $email = $kerberosUser->getAttribute('mail') ?? 'no@e.mail';
        $user
            ->setUsername($kerberosUser->getAttribute('uid')[0])
            ->setUsernameCanonical(($kerberosUser->getAttribute('uid')[0]))
            ->setEmail($email)
            ->setEmailCanonical($email)
            ->setEnabled(true)
            ->setLastLogin(new \DateTime())
            ->setIsADUser(true)
            ->setADUserId($kerberosUser->getAttribute($this->userIDAttribute)[0]);

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

    /**
     * @param User $user
     */
    public function updateLastLogin(User $user)
    {
        $user->setLastLogin(new \DateTime());
        $this->em->flush();
    }
}
