<?php

namespace App\Service;

use App\Entity\FactoryEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class FactoryService
 * @package App\Service
 */

class FactoryService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $internalFolder;
    protected $pathPermissions;
    protected $factoriesRepository;
    protected $serializerService;

    /**
     * FactoryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param SerializerService $serializerService
     */

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, SerializerService $serializerService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->serializerService = $serializerService;
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
    }

    /**
     * @param FactoryEntity $newFactory
     */

    public function createFactory(FactoryEntity $newFactory)
    {
        $this->em->persist($newFactory);
        $this->serializerService->serializeFactoriesAndSettings();
        $this->em->flush();
    }

    /**
     * This is called on factory property change submit
     */

    public function updateFactory()
    {
        $this->serializerService->serializeFactoriesAndSettings();
        $this->em->flush();
    }

    /**
     * @return FactoryEntity[]|array
     */

    public function getFactories()
    {
        return $this->factoriesRepository->findAll();
    }

    /**
     * @param integer $factoryId
     * @return FactoryEntity|null|object
     */

    public function findFactory(int $factoryId)
    {
        return $this->factoriesRepository->findOneById($factoryId);
    }

}
