<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\User;
use App\Serializer\Denormalizer\DateTimeAttributeDenormalizer;
use App\Serializer\Denormalizer\ArchiveEntityAttributeDenormalizer;
use App\Serializer\Denormalizer\PropertyExtractor\ArchiveEntityPropertyExtractor;
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

    /**
     * RecoveryService constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     */

    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
        $this->usersRepository = $this->em->getRepository('App:User');
        $this->factoriesRepository = $this->em->getRepository('App:FactoryEntity');
        $this->settingsRepository = $this->em->getRepository('App:SettingEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
    }

    public function restoreDatabase()
    {
        $fs = new Filesystem();
        $internalPath = $this->pathRoot . '/' . $this->internalFolder;
        if ($fs->exists($internalPath)) {
            $users = $this->locateUsers($internalPath);
            $FaS = $this->locateFactoriesAndSettings($internalPath);
            $entryFiles = $this->locateFiles();
            //$this->restoreUsers($users);
            //$this->restoreFactoriesAndSettings($FaS);
            $this->restoreEntries($entryFiles);

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
            array(new ArchiveEntityAttributeDenormalizer(), new ObjectNormalizer(null, null, null, new ArchiveEntityPropertyExtractor()), new ArrayDenormalizer()),
            array(new JsonEncoder()));
        foreach ($entryFiles as $entryFilePath) {
            $entryFile = file_get_contents($entryFilePath);
            $entry = $serializer->deserialize($entryFile, ArchiveEntryEntity::class, 'json');

            $entries[] = $entry;

            if ($entry->getMarkedByUser()) {
                $entry->setMarkedByUser($this->usersRepository->findOneByUsername($entry->getMarkedByUser()->getUsername()));
            }
            if ($entry->getModifiedByUser()) {
                $entry->setModifiedByUser($this->usersRepository->findOneByUsername($entry->getModifiedByUser()->getUsername()));
            }
            if ($entry->getRequestedByUsers()) {

                /*if (strpos($entry->getRequestedByUsers, ',') !== false) {
                    $users = explode(', ', $entry->getRequestedByUsers());
                } else {
                    $users[] = $entry->getRequestedByUsers();
                } */

               // if (count($entry->getRequestedByUsers()) > 0) {
                    $users = [];
                    foreach ($entry->getRequestedByUsers() as $username) {
                        $user = $this->usersRepository->findOneByUsername($username->getUsername());
                        $users[] = $user->getId();
                    }

                    $entry->setRequestedByUsers($users);
                //}
            }

            set_include_path('/var/www/archive/public_html/public/');
            $file = 'test.txt';
            $wr = file_get_contents($file);
            $wr = $wr . 'CataloguePath: ' . $entry->getCataloguePath() . "!!!!!!!!!!!!!!" . "\n\n";
            //$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";
            file_put_contents($file, $wr);

            //$this->em->persist($entry);
            }

            //$this->em->flush();
    }
}