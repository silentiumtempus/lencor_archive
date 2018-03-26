<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class UserService
 * @package App\Service
 */
class UserService
{
    protected $em;
    protected $container;
    protected $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
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
}
