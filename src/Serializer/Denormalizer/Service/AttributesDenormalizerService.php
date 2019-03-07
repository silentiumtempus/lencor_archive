<?php
declare(strict_types=1);

namespace App\Serializer\Denormalizer\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class AttributesDenormalizerService
 * @package App\Serializer\Denormalizer\Service
 */
class AttributesDenormalizerService
{
    protected $em;
    protected $usersRepository;
    protected $factoriesRepository;
    protected $settingsRepository;

    /**
     * AttributesDenormalizerService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->usersRepository = $this->em->getRepository('App:User');
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
        $this->settingsRepository = $this->em->getRepository('App:SettingEntity');
    }

    /**
     * @param string $key
     * @param string|null $value
     * @return User|\DateTime|null
     * @throws \Exception
     */
    public function denormalizeAttribute(string $key, string $value = null)
    {
        switch ($key) {
            case 'factory' :
                return $this->denormalizeFactoryAttribute($value);
                break;
            case 'setting' :
                return $this->denormalizeSettingAttribute($value);
                break;
            case 'markedByUser' :
            case 'modifiedByUser' :
            case 'addedByUser' :
                return $this->denormalizeUserAttribute($value);
                break;
            case 'lastModified' :
            case 'addTimestamp' :
            case 'passwordRequestedAt' :
                return $this->denormalizeTimestamp($value);
        }

        return null;
    }

    /**
     * @param array $data
     * @return null
     */
    public function denormalizeRequestedByUsers(array $data)
    {
        if (isset($data['requestedByUsers']) && is_string($data['requestedByUsers'])) {
            $users = [];
            if ($data['requestedByUsers'] !== "") {
                if (strpos($data['requestedByUsers'], ',') !== false) {
                    $userNames = explode(',', $data['requestedByUsers']);
                } else {
                    $userNames[] = $data['requestedByUsers'];
                }
                foreach ($userNames as $requestedByUser) {
                    $user = $this->usersRepository->findOneByUsername($requestedByUser);
                    $users[] = $user;
                }
                $data['requestedByUsers'] = $user = $this->usersRepository->findOneByUsername($users);
            } else {
                $data['requestedByUsers'] = null;
            }
        }

        return $data['requestedByUsers'];
    }

    /**
     * @param string|null $userAttribute
     * @return User|null
     */
    private function denormalizeUserAttribute(string $userAttribute = null)
    {
        if (isset($userAttribute) && is_string($userAttribute)) {
            $user = $this->usersRepository->findOneByUsername($userAttribute);

            return $user;
        } else {

            return null;
        }
    }

    /**
     * @param string $factoryName
     * @return User|null
     */
    private function denormalizeFactoryAttribute(string $factoryName)
    {
        if (isset($factoryName) && is_string($factoryName)) {
            $user = $this->factoriesRepository->findOneByFactoryName($factoryName);

            return $user;
        } else {

            return null;
        }
    }

    /**
     * @param string $settingName
     * @return User|null
     */
    private function denormalizeSettingAttribute(string $settingName)
    {
        if (isset($settingName) && is_string($settingName)) {
            $user = $this->settingsRepository->findOneBySettingName($settingName);

            return $user;
        } else {

            return null;
        }
    }

    /**
     * @param string $dateTime
     * @return \DateTime|null
     * @throws \Exception
     */
    private function denormalizeTimestamp(string $dateTime)
    {
        if (isset($dateTime) && is_string($dateTime)) {

            return new \DateTime($dateTime);
        } else {

            return null;
        }
    }
}
