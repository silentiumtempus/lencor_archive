<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class FileService
 * @package App\Services
 */
class FileService
{
    protected $em;
    protected $container;
    protected $folderService;
    protected $filesRepository;
    protected $foldersRepository;
    protected $userService;

    /**
     * FileService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FolderService $folderService
     * @param UserService $userService
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, FolderService $folderService, UserService $userService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->userService = $userService;
        $this->folderService = $folderService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
    }

    /**
     * @param int $fileId
     * @return mixed
     */
    public function getFileById(int $fileId)
    {
        return $this->filesRepository->findOneById($fileId);
    }

    /**
     * @param string $folderAbsPath
     * @param string $originalName
     * @return string
     */
    public function constructFileAbsPath(string $folderAbsPath, string $originalName)
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
     * @param string $filePath
     * @return string
     */
    public function getFileHttpUrl(string $filePath)
    {
        $httpRoot = $this->container->getParameter('lencor_archive.http_path');
        $httpPath = $httpRoot . $filePath;

        return $httpPath;
    }

    /**
     * @param FileEntity $fileArrayEntity
     * @param $file
     * @return FileEntity
     */
    public function createFileEntityFromArray(FileEntity $fileArrayEntity, $file)
    {
        $newFileEntity = clone $fileArrayEntity;
        $newFileEntity
            ->setFileName($file)
            ->setFiles(null);

        return $newFileEntity;
    }

    /**
     * @param FileEntity $newFileEntity
     * @param FolderEntity $parentFolder
     * @param string $originalName
     * @param User $user
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
     * @param int $fileId
     * @param int $userId
     * @return mixed
     */
    public function removeFile(int $fileId, int $userId)
    {
        $deletedFile = $this->filesRepository->findById($fileId);

        foreach ($deletedFile as $file) {
            $file
                ->setDeleteMark(true)
                ->setDeletedByUserId($userId);
        }
        $this->em->flush();

        return $deletedFile;
    }

    /**
     * @param int $fileId
     * @return mixed
     */
    public function restoreFile(int $fileId)
    {
        $restoredFile = $this->filesRepository->findById($fileId);
        foreach ($restoredFile as $file) {
            $file
                ->setDeleteMark(false)
                ->setDeletedByUserId(null)
                ->setRequestMark(false)
                ->setRequestedByUsers(null);
        }
        $this->em->flush();

        return $restoredFile;
    }

    /**
     * @param int $fileId
     * @param int $userId
     * @return mixed
     */
    public function requestFile(int $fileId, int $userId)
    {
        $requestedFile = $this->filesRepository->findById($fileId);
        foreach ($requestedFile as $file) {
            if ($file->getRequestedByUsers() != null) {
                $users = $file->getRequestedByUsers();
                if ((array_search($userId, $users, true)) === false) {
                    $users[] = $userId;
                }
            } else {
                $users[] = $userId;
            }
            $file
                ->setRequestMark(true)
                ->setRequestedByUsers($users)
                ->setRequestsCount(count($file->getRequestedByUsers()));
        }
        $this->em->flush();

        return $requestedFile;
    }

    /**
     * @param int $fileId
     * @return array
     */
    public function getFileRequesters(int $fileId)
    {
        $file = $this->filesRepository->findOneById($fileId);
        $requesterIds = $file->getRequestedByUsers();
        $requesters = $this->userService->getUsers($requesterIds);
        
        return $requesters;
    }
    
    /**
     * @param int $folderId
     * @param int $userId
     * @return bool
     */
    public function removeFilesByParentFolder(int $folderId, int $userId)
    {
        $childFiles = $this->filesRepository->findByParentFolder($folderId);
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getDeleteMark()) {
                    $childFile
                        ->setDeleteMark(true)
                        ->setDeletedByUserId($userId);
                }
            }
        }

        return true;
    }

    /**
     * @param int $folderId
     * @return mixed
     */
    public function showEntryFiles(int $folderId)
    {
        $files = $this->filesRepository->findByParentFolder($folderId);
        foreach ($files as $file) {
            if ($file->getRequestMark()) {
                $file->setRequestsCount(count($file->getRequestedByUsers()));
            }
        }

        return $files;
    }

    /**
     * @param int $fileId
     * @return mixed
     */
    public function reloadFileDetails(int $fileId)
    {
        return $this->filesRepository->findOneById($fileId);
    }
}