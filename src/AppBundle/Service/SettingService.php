<?php

namespace AppBundle\Service;

use AppBundle\Entity\SettingEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class SettingService
 * @package AppBundle\Service
 */
class SettingService
{
    protected $em;

    /**
     * SettingService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
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