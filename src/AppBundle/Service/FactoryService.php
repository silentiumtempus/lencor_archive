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

    /**
     * FactoryService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param FactoryEntity $newFactory
     */
    public function createFactory(FactoryEntity $newFactory)
    {
        $this->em->persist($newFactory);
        $this->em->flush();
    }
}