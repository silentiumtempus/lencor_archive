<?php

namespace AppBundle\Service;

use AppBundle\Entity\SettingEntity;
use Doctrine\ORM\EntityManager;

/**
 * Class SettingService
 * @package AppBundle\Service
 */
class SettingService
{
    protected $em;

    /**
     * SettingService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param SettingEntity $newSetting
     */
    public function createSetting(SettingEntity $newSetting)
    {
        $this->em->persist($newSetting);
        $this->em->flush();
    }
}