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

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createSetting(SettingEntity $newSetting)
    {
        $this->em->persist($newSetting);
        $this->em->flush();
    }
}