<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;

/**
 * Class FolderService
 * @package App\Services
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
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
    }

    /**
     * @param FolderEntity $parentFolder
     * @return string
     */
    public function constructFolderAbsPath(FolderEntity $parentFolder)
    {
        $folderAbsPath = $this->pathRoot;
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
    public function getFolderEntryId(int $folderId)
    {
        $folderNode = $this->foldersRepository->findOneById($folderId);
        return $folderNode->getRoot()->getArchiveEntry()->getId();
    }

    /**
     * @param $entryId
     * @return mixed
     */
    public function getRootFolder(int $entryId)
    {
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        return $folderId;
    }

    /**
     * @param integer $folderId
     * @return bool
     */
    public function isRoot(int $folderId)
    {
        $folderEntity = $this->foldersRepository->findOneById($folderId);

        return ($folderEntity->getId() == $folderEntity->getRoot()->getId()) ? true : false;
    }

    /**
     * @param $parentFolder
     * @return mixed
     */
    public function getParentFolder($parentFolder)
    {
        return $this->foldersRepository->findOneById($parentFolder);
    }

    /**
     * @param FolderEntity $parentFolder
     * @return array
     */
    public function getPath(FolderEntity $parentFolder)
    {
        return $this->foldersRepository->getPath($parentFolder);
    }

    /**
     * @param FolderEntity $newFolderEntity
     * @param ArchiveEntryEntity $newEntry
     * @param $userId
     */
    public function prepareNewRootFolder(FolderEntity $newFolderEntity, ArchiveEntryEntity $newEntry, int $userId)
    {
        $newFolderEntity
            ->setArchiveEntry($newEntry)
            ->setFolderName($newEntry->getYear() . "/" . $newEntry->getFactory()->getId() . "/" . $newEntry->getArchiveNumber())
            ->setAddedByUserId($userId)
            ->setDeleteMark(false)
            ->setDeletedByUserId(null)
            ->setSlug(null);
    }

    /**
     * @param FormInterface $folderAddForm
     * @param int $userId
     * @return FolderEntity
     */
    public function prepareNewFolder(FormInterface $folderAddForm, int $userId)
    {
        $newFolderEntity = $folderAddForm->getData();
        $parentFolder = $this->foldersRepository->findOneById($folderAddForm->get('parentFolder')->getViewData());
        $newFolderEntity->setParentFolder($parentFolder)
            ->setAddedByUserId($userId)
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
    public function removeFolder(int $folderId, int $userId, FileService $fileService)
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
    public function restoreFolder(int $folderId)
    {
        $restoredFolder = $this->foldersRepository->findById($folderId);
        foreach ($restoredFolder as $folder) {
            $folder->setDeleteMark(false);
            $folder->setDeletedByUserId(null);
        }
        $this->em->flush();

        return $restoredFolder;
    }

    public function requestFolder(int $folderId, int $userId, bool $recursive)
    {

        return null;
    }

    /**
     * @param $folderId
     * @return mixed
     */
    public function showEntryFolder(int $folderId)
    {
        $options = array();
        $folderNode = $this->foldersRepository->findOneById($folderId);
        $folderTree = $this->foldersRepository->childrenHierarchy($folderNode, true, $options, false);

        return $folderTree;
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function checkAndCreateFolders(ArchiveEntryEntity $archiveEntryEntity)
    {
        $fs = new Filesystem();
        $pathYear = $this->pathRoot . "/" . $archiveEntryEntity->getYear();
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
            if (!$fs->exists($pathEntry)) {
                $fs->mkdir($pathEntry, $this->pathPermissions);
            } else {
                $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! директория для новой ячейки: ' . $pathEntry . ' уже существует');
            }
            if (!$fs->exists($pathLogs)) {
                $fs->mkdir($pathLogs, $this->pathPermissions);
            } else {
                $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! директория логов: ' . $pathEntry . ' уже существует');
            }
        } catch (IOException $IOException) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка создания директории: ' . $IOException->getMessage());
        }

        return $pathEntry;
    }

}
