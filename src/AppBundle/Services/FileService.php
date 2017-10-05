<?php

namespace AppBundle\Services;

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

    public function __construct(EntityManager $entityManager, FolderService $folderService)
    {
        $this->em = $entityManager;
        $this->folderService = $folderService;
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
    }

    public function constructFileAbsPath($folderAbsPath, $originalName)
    {
        return $folderAbsPath . "/" . $originalName;
    }

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

    public function persistFile(FileEntity $fileEntity)
    {
        $this->em->persist($fileEntity);
        $this->em->flush();
    }

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
}