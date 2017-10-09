<?php

namespace AppBundle\Service;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Class FileService
 * @package AppBundle\Services
 */
class FileService
{
    protected $em;
    protected $folderService;
    protected $filesRepository;

    /**
     * FileService constructor.
     * @param EntityManager $entityManager
     * @param FolderService $folderService
     */
    public function __construct(EntityManager $entityManager, FolderService $folderService)
    {
        $this->em = $entityManager;
        $this->folderService = $folderService;
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getFileById($fileId)
    {
        return $this->filesRepository->findOneById($fileId);
    }

    /**
     * @param $folderAbsPath
     * @param $originalName
     * @return string
     */
    public function constructFileAbsPath($folderAbsPath, $originalName)
    {
        return $folderAbsPath . "/" . $originalName;
    }

    /**
     * @param FileEntity $newFileEntity
     * @param FolderEntity $parentFolder
     * @param string $originalName
     * @param User $user
     * @return FileEntity
     */
    public function prepareNewFile(FileEntity $newFileEntity, FolderEntity $parentFolder, string $originalName, User $user)
    {
        $parentFolder = $this->folderService->getParentFolder($parentFolder);
        $newFileEntity
            ->setParentFolder($parentFolder)
            ->setFileName($originalName)
            ->setAddedByUserId($user->getId())
            ->setDeleteMark(false)
            ->setSlug(null)
            ->setDeletedByUserId(null);

        return $newFileEntity;
    }

    /**
     * @param FileEntity $fileEntity
     */
    public function persistFile(FileEntity $fileEntity)
    {
        $this->em->persist($fileEntity);
        $this->em->flush();
    }

    //@TODO: Unite two methods below
    /**
     * @param $fileId
     * @param $userId
     * @return mixed
     */
    public function removeFile($fileId, $userId)
    {
        $deletedFile = $this->filesRepository->findById($fileId);

        foreach ($deletedFile as $file) {
            $file->setDeleteMark(true);
            $file->setDeletedByUserId($userId);
        }
        $this->em->flush();

        return $deletedFile;
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function restoreFile($fileId)
    {
        $restoredFile = $this->filesRepository->findById($fileId);
        foreach ($restoredFile as $file) {
            $file->setDeleteMark(false);
            $file->setDeletedByUserId(null);
        }
        $this->em->flush();

        return $restoredFile;
    }

    /**
     * @param $folderId
     * @param $userId
     * @return bool
     */
    public function removeFilesByParentFolder($folderId, $userId)
    {
        $childFiles = $this->filesRepository->findByParentFolder($folderId);
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getDeleteMark()) {
                    $childFile->setDeleteMark(true);
                    $childFile->setDeletedByUserId($userId);
                }
            }
        }

        return true;
    }
}