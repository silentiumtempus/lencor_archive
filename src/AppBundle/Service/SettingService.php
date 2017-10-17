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
    protected $settingsRepository;

    /**
     * SettingService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->settingsRepository = $this->em->getRepository('AppBundle:SettingEntity');
    }

    /**
     * @param SettingEntity $newSetting
     */
    public function createSetting(SettingEntity $newSetting)
    {
        $this->em->persist($newSetting);
        $this->em->flush();
    }

    public function findSettingsByFactory($factory)
    {
       return $this->settingsRepository->findByFactory($factory, array('id' => 'asc'));
    }
}