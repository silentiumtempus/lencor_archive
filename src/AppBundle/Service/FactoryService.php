<?php

namespace AppBundle\Service;

use AppBundle\Entity\FactoryEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class FactoryService
 * @package AppBundle\Service
 */
class FactoryService
{
    protected $em;
    protected $factoriesRepository;

    /**
     * FactoryService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->factoriesRepository = $this->em->getRepository('AppBundle:FactoryEntity');
    }

    /**
     * @param FactoryEntity $newFactory
     */
    public function createFactory(FactoryEntity $newFactory)
    {
        $this->em->persist($newFactory);
        $this->em->flush();
    }

    /**
     * This is called on factory property change submit
     */
    public function updateFactory()
    {
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
     * @param int $factoryId
     * @return FactoryEntity|null|object
     */
    public function findFactory(int $factoryId)
    {
        return $this->factoriesRepository->find($factoryId);
    }
}