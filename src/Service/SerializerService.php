<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\SettingEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $internalFolder;
    protected $pathPermissions;
    protected $usersRepository;
    protected $factoriesRepository;
    protected $settingsRepository;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
        $this->pathPermissions = $this->container->getParameter('archive.storage_permissions');
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
        $this->usersRepository = $this->em->getRepository('App:User');
    }

    private function checkAndCreateInternalFolderPath()
    {
        $fs = new Filesystem();
        $internalPath = $this->constructInternalFolderPath();
        if (!$fs->exists($internalPath)) {
            $fs->mkdir($internalPath, $this->pathPermissions);
        }

        return $internalPath;
    }

    /**
     * @return string
     */

    private function constructInternalFolderPath()
    {
        return $this->pathRoot . '/' . $this->internalFolder . '/';
    }

    /**
     * @return ObjectNormalizer
     */

    private function prepareJSONNormalizer()
    {
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setSerializer(new Serializer([$normalizer], [$encoder]));
        $normalizer->setCircularReferenceHandler(function ($object) {

            return $object->__toString();
        });

        return $normalizer;
    }

    /**
     * @return bool
     */

    public function serializeFactoriesAndSettings()
    {
        $fs = new Filesystem();
        $normalizer = $this->prepareJSONNormalizer();
        $normalizer->setIgnoredAttributes(array('settings' => 'id', 'factory'));
        $internalPath = $this->checkAndCreateInternalFolderPath();
        $internalFactoriesFile = $internalPath . "factories_and_settings";
        $factories = $this->factoriesRepository->findAll();
        if ($factories) {
            $factoriesArray = '[';
            foreach ($factories as $factory) {
                $factory = $normalizer->normalize($factory);
                $factoriesArray .= json_encode($factory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ', ';
            }
            $factoriesArray = rtrim($factoriesArray, ', ');
            $factoriesArray .= ']';
            $fs->dumpFile($internalFactoriesFile, $factoriesArray);
        }

        return true;
    }

    /**
     * @return bool
     */

    public function serializeUsers()
    {
        $fs = new Filesystem();
        $normalizer = $this->prepareJSONNormalizer();
        $internalPath = $this->checkAndCreateInternalFolderPath();
        $internalUsersFile = $internalPath . "users";
        $users = $this->usersRepository->findAll();
        $normalizer->setIgnoredAttributes(array(
            'user' => 'salt', 'plainPassword', 'accountNonExpired', 'accountNonLocked', 'superAdmin', 'groups', 'groupNames', 'credentialsNonExpired', 'passwordRequestedAt', 'confirmationToken'
        ));
        $timeStamp = function ($dateTime) {
            return (!$dateTime instanceof \DateTime) ?: $dateTime->format(\DateTime::ISO8601);
        };
        $normalizer->setCallbacks(array(
            'lastLogin' => $timeStamp,
            'passwordRequestedAt' => $timeStamp
        ));
        //@TODO: Investigate how it's possible to use normalizer directly with array of objects
        if ($users) {
            $usersArray = '[';
            foreach ($users as $user) {
                $user = $normalizer->normalize($user);
                $usersArray .= json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ', ';
            }
            $usersArray = rtrim($usersArray, ', ');
            $usersArray .= ']';
            $fs->dumpFile($internalUsersFile, $usersArray);
        }

        return true;
    }

    /**
     * @param ArchiveEntryEntity $newEntry
     * @param string $entryPath
     * @param bool $isNew
     * @return bool
     */

    public function serializeEntry(ArchiveEntryEntity $newEntry, string $entryPath, bool $isNew)
    {
        //try {
        $filename = $entryPath . "/" . $newEntry->getArchiveNumber() . ".entry";
        $fs = new Filesystem();
        if ($isNew && $fs->exists($filename)) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка: файл ячейки: ' . $filename . ' уже существует. Продолжение прервано.');
            throw new IOException('Файл ячейки уже существует');
        } else {
            $fs->touch($filename);
            $normalizer = $this->prepareJSONNormalizer();
            $normalizer->setIgnoredAttributes(array(
                'childFolders' => 'lft', 'rgt', 'lvl', 'requestsCount',
                'files' => 'id', 'uploadedFiles', 'requestsCount'
            ));
            $timeStamp = function ($dateTime) {
                return (!$dateTime instanceof \DateTime) ?: $dateTime->format(\DateTime::ISO8601);
            };
            $factoryCallback = function ($factory) {
                return (!$factory instanceof FactoryEntity) ?: $factory->getFactoryName();
            };
            $settingCallback = function ($setting) {
                return (!$setting instanceof SettingEntity) ?: $setting->getSettingName();
            };
            $userCallback = function ($user) {
                return (!$user instanceof User) ?: $user->getUsername();
            };
            $requestedByCallback = function ($users) {
                $usersString = '';
                if (is_array($users)) {
                    $users = $this->usersRepository->findById($users);
                    if ($users) {
                        foreach ($users as $user) {
                            $usersString .= $user->getUsername() . ',';
                        }
                        $usersString = rtrim($usersString, ',');
                    }
                }

                return $usersString;
            };
            $normalizer->setCallbacks(array(
                'addTimestamp' => $timeStamp,
                'lastModified' => $timeStamp,
                'lastLogin' => $timeStamp,
                'factory' => $factoryCallback,
                'setting' => $settingCallback,
                'addedByUser' => $userCallback,
                'markedByUser' => $userCallback,
                'modifiedByUser' => $userCallback,
                'requestedByUsers' => $requestedByCallback
            ));
            $array = $normalizer->normalize($newEntry);
            $entryJSON = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $fs->dumpFile($filename, $entryJSON);

            return true;
            //} catch (\Exception $exception) {
            //     $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка :' . $exception->getMessage());

            //    return false;
            //}
        }
    }
}