<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class FolderService
 * @package AppBundle\Services
 */
class FolderService
{
    protected $em;
    protected $container;
    protected $foldersRepository;
    protected $filesRepository;
    protected $pathRoot;
    protected $pathPermissions;

    /**
     * FolderService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
    }

    /**
     * @param $rootPath
     * @param $parentFolder
     * @return string
     */
    public function constructFolderAbsPath($rootPath, $parentFolder)
    {
        $folderAbsPath = $rootPath;
        $binaryPath = $this->foldersRepository->getPath($parentFolder);

        foreach ($binaryPath as $folderName) {
            $folderAbsPath .= "/" . $folderName;
        }

        return $folderAbsPath;
    }

    /**
     * @param $folderId
     * @return mixed
     */
    public function getFolderEntryId($folderId)
    {
        $folderNode = $this->foldersRepository->findOneById($folderId);
        return $folderNode->getRoot()->getArchiveEntry()->getId();
    }

    /**
     * @param $entryId
     * @return mixed
     */
    public function getRootFolder($entryId)
    {
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        return $folderId;
    }

    /**
     * @param FolderEntity $parentFolder
     * @return mixed
     */
    public function getParentFolder(FolderEntity $parentFolder)
    {
        return $this->foldersRepository->findOneById($parentFolder);
    }

    /**
     * @param FolderEntity $newFolderEntity
     * @param ArchiveEntryEntity $newEntry
     * @param $userId
     */
    public function prepareNewRootFolder(FolderEntity $newFolderEntity, ArchiveEntryEntity $newEntry, $userId)
    {
        $newFolderEntity->setArchiveEntry($newEntry);
        $newFolderEntity->setFolderName($newEntry->getYear() . "/" . $newEntry->getFactory()->getId() . "/" . $newEntry->getArchiveNumber());
        $newFolderEntity->setAddedByUserId($userId);
        $newFolderEntity->setDeleteMark(false);
        $newFolderEntity->setDeletedByUserId(null);
    }

    /**
     * @param FolderEntity $newFolderEntity
     * @param FolderEntity $parentFolder
     * @param User $user
     * @return FolderEntity
     */
    public function prepareNewFolder(FolderEntity $newFolderEntity, FolderEntity $parentFolder, User $user)
    {
        $parentFolder = $this->getParentFolder($parentFolder);
        $newFolderEntity->setParentFolder($parentFolder)
            ->setAddedByUserId($user->getId())
            ->setDeleteMark(false)
            ->setDeletedByUserId(null)
            ->setSlug(null);

        return $newFolderEntity;
    }

    /**
     * @param FolderEntity $folderEntity
     */
    public function persistFolder(FolderEntity $folderEntity)
    {
        $this->em->persist($folderEntity);
        $this->em->flush();
    }

    /**
     * @param $folderId
     * @param $userId
     * @param FileService $fileService
     * @return mixed
     */
    public function removeFolder($folderId, $userId, FileService $fileService)
    {
        $deletedFolder = $this->foldersRepository->findById($folderId);
        foreach ($deletedFolder as $folder) {
            $folderChildren = $this->foldersRepository->getChildren($folder, false, null, null, true);
            if ($folderChildren) {
                foreach ($folderChildren as $childFolder) {
                    if (!$childFolder->getDeleteMark()) {
                        $childFolder->setDeleteMark(true);
                        $childFolder->setDeletedByUserId($userId);
                        $fileService->removeFilesByParentFolder($folderId, $userId);
                    }
                }
            }
        }
        $this->em->flush();

        return $deletedFolder;
    }

    /**
     * @param $folderId
     * @return mixed
     */
    public function restoreFolder($folderId)
    {
        $restoredFolder = $this->foldersRepository->findById($folderId);
        foreach ($restoredFolder as $folder) {
            $folder->setDeleteMark(false);
            $folder->setDeletedByUserId(null);
        }
        $this->em->flush();

        return $restoredFolder;
    }

    /**
     * @param $folderId
     * @return mixed
     */
    public function showEntryFolder($folderId)
    {
        $options = array();
        $folderNode = $this->foldersRepository->findOneById($folderId);
        $folderTree = $this->foldersRepository->childrenHierarchy($folderNode, true, $options, false);

        return $folderTree;
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @return string
     */
    public function checkAndCreateFolders(ArchiveEntryEntity $archiveEntryEntity)
    {
        $pathYear = $this->pathRoot . "/" . $archiveEntryEntity->getYear();
        $pathFactory = $pathYear . "/" . $archiveEntryEntity->getFactory()->getId();
        $pathEntry = $pathFactory . "/" . $archiveEntryEntity->getArchiveNumber();

        try {
            if (!$fs->exists($pathYear)) {
                $fs->mkdir($pathYear, $this->pathPermissions);
            }
            if (!$fs->exists($pathFactory)) {
                $fs->mkdir($pathFactory, $this->pathPermissions);
            }
            if (!$fs->exists($pathEntry)) {
                $fs->mkdir($pathEntry, $this->pathPermissions);
            } else {
                $this->container->addFlash('warning', 'Внимание: директория для новой ячейки: ' . $pathEntry . ' уже существует');
            }
        } catch (IOException $IOException) {
            $this->container->addFlash('danger', 'Ошибка создания директории: ' . $IOException->getMessage());
        }

        return $pathEntry;
    }
}