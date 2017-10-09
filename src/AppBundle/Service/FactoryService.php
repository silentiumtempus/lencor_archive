<?php

namespace AppBundle\Service;

use AppBundle\Entity\FactoryEntity;
use Doctrine\ORM\EntityManager;

/**
 * Class FactoryService
 * @package AppBundle\Service
 */
class FactoryService
{
    protected $em;

    /**
     * FactoryService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
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