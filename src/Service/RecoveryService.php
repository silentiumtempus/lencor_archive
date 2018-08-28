<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class RecoveryService
 * @package App\Service
 */
class RecoveryService
{
    protected $container;
    protected $pathRoot;
    protected $internalFolder;

    /**
     * RecoveryService constructor.
     * @param ContainerInterface $container
     */

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->internalFolder = $this->container->getParameter('archive.internal.folder_name');
    }

    public function restoreDatabase()
    {
        $fs = new Filesystem();
        $internalPath = $this->pathRoot . '/' . $this->internalFolder;
        if ($fs->exists($internalPath)) {
            $users = $this->locateUsers($internalPath);
            $FaS = $this->locateFactoriesAndSettings($internalPath);
            $files = $this->locateFiles();
        }
    }

    /**
     * @return array
     */

    private function locateFiles()
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

}