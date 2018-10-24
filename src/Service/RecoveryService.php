<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use App\Serializer\Denormalizer\DateTimeAttributeDenormalizer;
use App\Serializer\Denormalizer\EntryAttributesDenormalizer;
use App\Serializer\Denormalizer\PropertyExtractor\FactoryEntityPropertyExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class RecoveryService
 * @package App\Service
 */

class RecoveryService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $internalFolder;
    protected $usersRepository;
    protected $factoriesRepository;
    protected $settingsRepository;
    protected $entriesRepository;
    protected $foldersRepository;
    protected $filesRepository;
    protected $entryAttributeDenormalizer;

    /**
     * RecoveryService constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     * @param EntryAttributesDenormalizer $entryAttributeDenormalizer
     */

    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager, EntryAttributesDenormalizer $entryAttributeDenormalizer)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
        $this->usersRepository = $this->em->getRepository('App:User');
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
        $this->settingsRepository = $this->em->getRepository('App:SettingEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->entryAttributeDenormalizer = $entryAttributeDenormalizer;

    }

    /**
     * Main method to trigger database restoration
     */

    public function restoreDatabase()
    {
        try {
            $fs = new Filesystem();
            $internalPath = $this->pathRoot . '/' . $this->internalFolder;
            if ($fs->exists($internalPath)) {
                $users = $this->locateUsers($internalPath);
                $FaS = $this->locateFactoriesAndSettings($internalPath);
                $entryFiles = $this->locateFiles();
                $this->restoreUsers($users);
                $this->restoreFactoriesAndSettings($FaS);
                $this->restoreEntries($entryFiles);
            }
            $this->container->get('session')->getFlashBag()->add('success', 'База успешно восстановлена');
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка при восстановлении БД: ' . $exception->getMessage() . $exception->getTraceAsString());
        }
    }

    /**
     * @return array
     */

    public function locateFiles()
    {
        $finder = new Finder();
        $finder
            ->files()->name('*.entry')
            ->in($this->pathRoot)
            ->exclude('logs');
        $entryFiles = iterator_to_array($finder);

        return $entryFiles;
    }

    /**
     * @param string $internalPath
     * @return bool|string
     */

    private function locateUsers(string $internalPath)
    {
        return file_get_contents($internalPath . '/' . 'users');
    }

    /**
     * @param string $internalPath
     * @return bool|string
     */

    private function locateFactoriesAndSettings(string $internalPath)
    {
        return file_get_contents($internalPath . '/' . 'factories_and_settings');
    }

    /**
     * @param string $file
     */

    private function restoreUsers(string $file)
    {
        $serializer = new Serializer(
            array(new DateTimeAttributeDenormalizer(), new GetSetMethodNormalizer(), new ArrayDenormalizer()),
            array(new JsonEncoder()));
        $users = $serializer->deserialize($file, User::class . '[]', 'json');
        foreach ($users as $user) {
            if (!$this->usersRepository->findOneByUsername($user->getUsername())) {
                $this->em->persist($user);
            }
        }
        $this->em->flush();
    }

    /**
     * @param string $file
     */

    private function restoreFactoriesAndSettings(string $file)
    {
        $serializer = new Serializer(
            array(new ObjectNormalizer(null, null, null, new FactoryEntityPropertyExtractor()), new ArrayDenormalizer()),
            array(new JsonEncoder()));
        $factories = $serializer->deserialize($file, FactoryEntity::class . '[]', 'json');
        foreach ($factories as $factory) {
            $factoryExists = $this->factoriesRepository->findOneByFactoryName($factory->getFactoryName());
            if (!$factoryExists) {
                $this->em->persist($factory);
            }
            foreach ($factory->getSettings() as $setting) {
                if (!$this->settingsRepository->findOneBySettingName($setting->getSettingName())) {
                    if ($factoryExists) {
                        $setting->setFactory($factoryExists);
                    } else {
                        $setting->setFactory($factory);
                    }
                    $this->em->persist($setting);
                }
            }
        }
        $this->em->flush();
    }

    /**
     * @param array $entryFiles
     */

    private function restoreEntries(array $entryFiles)
    {
        $serializer = new Serializer(
            array($this->entryAttributeDenormalizer, new ArrayDenormalizer()),
            array(new JsonEncoder()));
        foreach ($entryFiles as $entryFilePath) {
            //@TODO: improve partial database restoration
            $entryFile = file_get_contents($entryFilePath);
            $entry = $serializer->deserialize($entryFile, ArchiveEntryEntity::class, 'json');
            if (!$this->entriesRepository->findOneByArchiveNumber($entry->getArchiveNumber())) {
                $rootFolder = $entry->getCataloguePath();
                $rootFolder->setArchiveEntry($entry);
                if ($rootFolder->getChildFolders() && count($rootFolder->getChildFolders()) > 0) {
                    $this->addChildFolders($rootFolder, $rootFolder);
                }
                $this->em->persist($entry);
                $this->em->flush();
            }
        }
    }

    /**
     * @param FolderEntity $rootFolder
     * @param FolderEntity $folder
     */

    private function addChildFolders(FolderEntity $rootFolder, FolderEntity $folder)
    {
        foreach ($folder->getChildFolders() as $childFolder) {
            $childFolder
                ->setRoot($rootFolder)
                ->setParentFolder($folder);
            $this->addChildFiles($folder);
            if ($childFolder->getChildFolders() && count($childFolder->getChildFolders()) > 0) {
                $this->addChildFolders($rootFolder, $childFolder);
            }
        }
    }

    /**
     * @param FolderEntity $parentFolder
     */

    private function addChildFiles(FolderEntity $parentFolder)
    {
        if ($parentFolder->getFiles()) {
            foreach ($parentFolder->getFiles()->getIterator() as $key => $file) {
                $file->setParentFolder($parentFolder);
            }
        }
    }
}