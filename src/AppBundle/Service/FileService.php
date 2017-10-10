<?php

namespace AppBundle\Service;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class FileService
 * @package AppBundle\Services
 */
class FileService
{
    protected $em;
    protected $container;
    protected $folderService;
    protected $filesRepository;
    protected $foldersRepository;

    /**
     * FileService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FolderService $folderService
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, FolderService $folderService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->folderService = $folderService;
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
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
     * @param FileEntity $requestedFile
     * @return null|string
     */
    public function getFilePath(FileEntity $requestedFile)
    {
        $path = null;
        $binaryPath = $this->foldersRepository->getPath($requestedFile->getParentFolder());
        foreach ($binaryPath as $folderName) {
            $path .= "/" . $folderName;
        }
        $path .= "/" . $requestedFile->getFileName();

        return $path;
    }

    /**
     * @param $filePath
     * @return string
     */
    public function getFileHttpUrl($filePath)
    {
        $httpRoot = $this->container->getParameter('lencor_archive.http_path');
        $httpPath = $httpRoot . $filePath;

        return $httpPath;
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

    /**
     * @param $folderId
     * @return mixed
     */
    public function showEntryFiles($folderId)
    {
        return $fileList = $this->filesRepository->findByParentFolder($folderId);
    }
}