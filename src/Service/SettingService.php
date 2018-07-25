<?php

namespace App\Service;

use App\Entity\SettingEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class SettingService
 * @package App\Service
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
        $this->settingsRepository = $this->em->getRepository('App:SettingEntity');
    }

    /**
     * @param SettingEntity $newSetting
     */
    public function createSetting(SettingEntity $newSetting)
    {
        $this->em->persist($newSetting);
        $this->em->flush();
    }

    /**
     * @param integer $factory
     * @return mixed
     */
    public function findSettingsByFactoryId(int $factory)
    {
        return $this->settingsRepository->findByFactory($factory, array('id' => 'asc'));
    }

    /**
     * This is called on setting property change submit
     */
    public function updateSetting()
    {
        $this->em->flush();
    }
}
