<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CommonArchiveService
 * @package App\Service
 */
class CommonArchiveService
{
    protected $em;
    protected $container;
    protected $filesRepository;
    protected $foldersRepository;
    protected $entriesRepository;
    protected $userService;
    protected $pathRoot;
    protected $pathPermissions;
    protected $deletedFolder;

    /**
     * CommonArchiveService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param UserService $userService
     */

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, UserService $userService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->userService = $userService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
        $this->deletedFolder = $this->container->getParameter('archive.deleted.folder_name');
    }

    /**
     * @param int $id
     * @param string $type
     * @return array
     */

    public function getRequesters(int $id, string $type)
    {
        $repository = null;
        switch ($type) {
            case 'file' :
                $repository = $this->filesRepository;
                break;
            case 'folder' :
                $repository = $this->foldersRepository;
                break;
            case 'entry' :
                $repository = $this->entriesRepository;
        }
        $source = $repository->findOneById($id);
        $requesterIds = $source->getRequestedByUsers();
        $requesters = $this->userService->getUsers($requesterIds);

        return $requesters;
    }

    /**
     * @param FolderEntity $parentFolder
     * @param bool $deleteState
     */

    public function changeDeletesQuantity(FolderEntity $parentFolder, bool $deleteState)
    {
        $binaryPath = $this->foldersRepository->getPath($parentFolder);
        foreach ($binaryPath as $folder) {
            if ($deleteState) {
                $folder->setDeletedChildren($folder->getDeletedChildren() + 1);
            } else {
                $folder->setDeletedChildren($folder->getDeletedChildren() - 1);
            }
        }
        // @TODO: Temporary solution, find out how to use joins with FOSElasticaBundle
        $rootFolder = $parentFolder->getRoot();
        $entry = $parentFolder->getRoot()->getArchiveEntry();
        $entry->setDeletedChildren($rootFolder->getDeletedChildren());
    }

    /**
     * @param FolderEntity $folder
     * @param array $foldersArray
     * @param string $i
     * @return int|null
     */

    public function addFolderIdToArray(FolderEntity $folder, array $foldersArray, string $i)
    {
        if (!array_search($folder->getId(), $foldersArray[$i])) {

            return $folder->getId();
        }

        return null;
    }

    /**
     * Directory for deleted entries initialization
     */
    public function checkAndCreateDeletedFolder()
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->pathRoot . "/deleted")) {
            $fs->mkdir($this->pathRoot . "/deleted");
        }
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param bool $isNew
     * @param bool $isDeleted
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    public function checkAndCreateFolders(ArchiveEntryEntity $archiveEntryEntity, bool $isNew, bool $isDeleted)
    {
        $fs = new Filesystem();

        if ($isDeleted) {
            $this->checkAndCreateDeletedFolder();
            $pathYear = $this->pathRoot . "/" . $this->deletedFolder . "/" . $archiveEntryEntity->getYear();
        } else {
            $pathYear = $this->pathRoot . "/" . $archiveEntryEntity->getYear();
        }
        $pathFactory = $pathYear . "/" . $archiveEntryEntity->getFactory()->getId();
        $pathEntry = $pathFactory . "/" . $archiveEntryEntity->getArchiveNumber();
        $pathLogs = $pathEntry . "/logs";

        try {
            if (!$fs->exists($pathYear)) {
                $fs->mkdir($pathYear, $this->pathPermissions);
            }
            if (!$fs->exists($pathFactory)) {
                $fs->mkdir($pathFactory, $this->pathPermissions);
            }
            if ($isNew && !$isDeleted) {
                if (!$fs->exists($pathEntry)) {
                    $fs->mkdir($pathEntry, $this->pathPermissions);
                } else {
                    $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! Директория для новой ячейки: ' . $pathEntry . ' уже существует');
                }
                if (!$fs->exists($pathLogs)) {
                    $fs->mkdir($pathLogs, $this->pathPermissions);
                } else {
                    $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! директория логов: ' . $pathEntry . ' уже существует');
                }
            } else {
                if ($fs->exists($pathEntry)) {
                    $this->container->get('session')->getFlashBag()->add('danger', 'Внимание! Директория назначения: ' . $pathEntry . ' уже существует. Операция прервана.');

                } else {
                    if ($fs->exists($pathLogs)) {
                        $this->container->get('session')->getFlashBag()->add('danger', 'Внимание! Директория логов: ' . $pathLogs . 'уже существует. Операция прервана.');
                    }
                }
            }

        } catch (IOException $IOException) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка создания директории: ' . $IOException->getMessage());
        }

        return $pathEntry;
    }
}