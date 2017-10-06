<?php

namespace AppBundle\Service;

use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Class FolderService
 * @package AppBundle\Services
 */
class FolderService
{
    protected $em;
    protected $foldersRepository;
    protected $filesRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
    }

    public function constructFolderAbsPath($rootPath, $parentFolder)
    {
        $folderAbsPath = $rootPath;
        $binaryPath = $this->foldersRepository->getPath($parentFolder);

        foreach ($binaryPath as $folderName) {
            $folderAbsPath .= "/" . $folderName;
        }

        return $folderAbsPath;
    }

    public function getFolderEntryId($folderId)
    {
            $folderNode = $this->foldersRepository->findOneById($folderId);
            return $folderNode->getRoot()->getArchiveEntry()->getId();
    }

    public function getRootFolder($entryId)
    {
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        return $folderId;
    }

    public function getParentFolder(FolderEntity $parentFolder)
    {
        return $this->foldersRepository->findOneById($parentFolder);
    }

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

    public function persistFolder(FolderEntity $folderEntity)
    {
        $this->em->persist($folderEntity);
        $this->em->flush();
    }

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
}