<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\SettingEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
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
    protected $commonArchiveService;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, CommonArchiveService $archiveService)
    {
        $this->em = $em;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
        $this->commonArchiveService = $archiveService;
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
        $this->usersRepository = $this->em->getRepository('App:User');
    }

    /**
     * @return string
     */

    public function constructInternalFolderPath()
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
        $normalizer->setSerializer(new Serializer(array($normalizer), array($encoder)));
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
        $normalizer->setIgnoredAttributes(array('settings' => 'id'));
        $internalPath = $this->constructInternalFolderPath();
        if (!$fs->exists($internalPath)) {
            $fs->mkdir($internalPath, $this->pathPermissions);
        }
        $internalFactoriesFile = $internalPath . "factories_and_settings";
        $factories = $this->factoriesRepository->findAll();
        if ($factories)
        {
            $factoriesArray = '';
            foreach ($factories as $factory)
            {
                $factory = $normalizer->normalize($factory);
                $factoriesArray .= json_encode($factory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
            $fs->dumpFile($internalFactoriesFile, $factoriesArray);
        }

        return true;
    }

    /**
     * @param ArchiveEntryEntity $newEntry
     * @param string $filename
     * @return bool
     */

    public function serializeEntry(ArchiveEntryEntity $newEntry, string $filename)
    {
        //try {
        $fs = new Filesystem();
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
            if (is_array($users)) {
                $users = $this->usersRepository->findById($users);
            }
            $usersString = '';
            if ($users) {
                foreach ($users as $user) {
                    $usersString .= $user->getUsername() . ',';
                }
                $usersString = rtrim($usersString, ',');
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
        //file_put_contents($filename, $entryJSONFile);
        $fs->dumpFile($filename, $entryJSON);

        return true;
        //} catch (\Exception $exception) {
        //     $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка :' . $exception->getMessage());

        //    return false;
        //}
    }
}