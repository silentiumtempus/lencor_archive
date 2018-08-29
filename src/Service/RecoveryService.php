<?php

namespace App\Service;

use App\Serializer\Normalizer\DateTimeAttributeNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
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
    }

    public function restoreDatabase()
    {
        $fs = new Filesystem();
        $internalPath = $this->pathRoot . '/' . $this->internalFolder;
        if ($fs->exists($internalPath)) {
            $users = $this->locateUsers($internalPath);
            $FaS = $this->locateFactoriesAndSettings($internalPath);
            $files = $this->locateFiles();
            $this->restoreUsers($users);
            $this->restoreFactoriesAndSettings($FaS);


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

    private function restoreFactoriesAndSettings(string $file)
    {
        $serializer = new Serializer(
            array(new GetSetMethodNormalizer(), new ArrayDenormalizer()),
            array(new JsonEncoder()));
        $factories = $serializer->deserialize($file, 'App\Entity\FactoryEntity[]', 'json');
        foreach ($factories as $factory) {
            if (!$this->factoriesRepository->findOneByFactoryName($factory->getFactoryName())) {
                $this->em->persist($factory);
            }
        }
        $this->em->flush();
    }

    /**
     * @param string $file
     */

    private function restoreUsers(string $file)
    {
        $serializer = new Serializer(
            array(new DateTimeAttributeNormalizer(), new GetSetMethodNormalizer(), new ArrayDenormalizer()),
            array(new JsonEncoder()));
        $users = $serializer->deserialize($file, 'App\Entity\User[]', 'json');
        foreach ($users as $user) {
            if (!$this->usersRepository->findOneByUsername($user->getUsername())) {
                $this->em->persist($user);
            }
        }
        $this->em->flush();
    }

}