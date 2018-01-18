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
     * @return FactoryEntity[]|array
     */
    public function getFactories()
    {
        return $this->factoriesRepository->findAll();
    }
}